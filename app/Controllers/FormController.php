<?php

namespace App\Controllers;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FormController extends Controller
{
    /**
     * 获取表单列表
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'keyword' => 'nullable|string',
            'status' => 'nullable|boolean',
            'type' => 'nullable|string',
        ]);

        $query = Form::query()
            ->when($request->keyword, function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->keyword}%");
            })
            ->when($request->status !== null, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->type, function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->with('creator');

        $forms = $query->paginate($request->limit ?? 20);

        return response()->json([
            'total' => $forms->total(),
            'per_page' => $forms->perPage(),
            'current_page' => $forms->currentPage(),
            'last_page' => $forms->lastPage(),
            'data' => $forms->items(),
        ]);
    }

    /**
     * 创建表单
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|string|in:normal,approval',
            'status' => 'required|boolean',
            'fields' => 'required|array',
            'fields.*.name' => 'required|string|max:50',
            'fields.*.label' => 'required|string|max:50',
            'fields.*.type' => 'required|string|in:text,textarea,radio,checkbox,select,date,file',
            'fields.*.required' => 'required|boolean',
            'fields.*.placeholder' => 'nullable|string|max:100',
            'fields.*.default_value' => 'nullable|string|max:500',
            'fields.*.options' => 'nullable|array',
            'fields.*.validation_rules' => 'nullable|array',
            'fields.*.order' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($request) {
            $form = Form::create([
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'status' => $request->status,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($request->fields as $fieldData) {
                $form->fields()->create([
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label'],
                    'type' => $fieldData['type'],
                    'required' => $fieldData['required'],
                    'placeholder' => $fieldData['placeholder'] ?? null,
                    'default_value' => $fieldData['default_value'] ?? null,
                    'options' => $fieldData['options'] ?? null,
                    'validation_rules' => $fieldData['validation_rules'] ?? null,
                    'order' => $fieldData['order'] ?? 0,
                ]);
            }

            return response()->json($form->load('fields'), 201);
        });
    }

    /**
     * 获取表单详情
     */
    public function show(Form $form)
    {
        return response()->json($form->load(['fields', 'creator']));
    }

    /**
     * 更新表单
     */
    public function update(Request $request, Form $form)
    {
        $request->validate([
            'title' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'sometimes|boolean',
            'fields' => 'sometimes|array',
            'fields.*.id' => 'nullable|exists:form_fields,id',
            'fields.*.name' => 'required|string|max:50',
            'fields.*.label' => 'required|string|max:50',
            'fields.*.type' => 'required|string|in:text,textarea,radio,checkbox,select,date,file',
            'fields.*.required' => 'required|boolean',
            'fields.*.placeholder' => 'nullable|string|max:100',
            'fields.*.default_value' => 'nullable|string|max:500',
            'fields.*.options' => 'nullable|array',
            'fields.*.validation_rules' => 'nullable|array',
            'fields.*.order' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($request, $form) {
            $form->update([
                'title' => $request->title ?? $form->title,
                'description' => $request->description ?? $form->description,
                'status' => $request->status ?? $form->status,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->has('fields')) {
                $existingFieldIds = $form->fields->pluck('id')->toArray();
                $updatedFieldIds = [];

                foreach ($request->fields as $fieldData) {
                    if (isset($fieldData['id'])) {
                        // 更新现有字段
                        $field = $form->fields()->findOrFail($fieldData['id']);
                        $field->update([
                            'name' => $fieldData['name'],
                            'label' => $fieldData['label'],
                            'type' => $fieldData['type'],
                            'required' => $fieldData['required'],
                            'placeholder' => $fieldData['placeholder'] ?? null,
                            'default_value' => $fieldData['default_value'] ?? null,
                            'options' => $fieldData['options'] ?? null,
                            'validation_rules' => $fieldData['validation_rules'] ?? null,
                            'order' => $fieldData['order'] ?? 0,
                        ]);
                        $updatedFieldIds[] = $fieldData['id'];
                    } else {
                        // 创建新字段
                        $field = $form->fields()->create([
                            'name' => $fieldData['name'],
                            'label' => $fieldData['label'],
                            'type' => $fieldData['type'],
                            'required' => $fieldData['required'],
                            'placeholder' => $fieldData['placeholder'] ?? null,
                            'default_value' => $fieldData['default_value'] ?? null,
                            'options' => $fieldData['options'] ?? null,
                            'validation_rules' => $fieldData['validation_rules'] ?? null,
                            'order' => $fieldData['order'] ?? 0,
                        ]);
                        $updatedFieldIds[] = $field->id;
                    }
                }

                // 删除不再存在的字段
                $fieldsToDelete = array_diff($existingFieldIds, $updatedFieldIds);
                if (!empty($fieldsToDelete)) {
                    $form->fields()->whereIn('id', $fieldsToDelete)->delete();
                }
            }

            return response()->json($form->load('fields'));
        });
    }

    /**
     * 删除表单
     */
    public function destroy(Form $form)
    {
        DB::transaction(function () use ($form) {
            $form->fields()->delete();
            $form->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * 获取表单字段
     */
    public function fields(Form $form)
    {
        return response()->json($form->fields()->orderBy('order')->get());
    }
}