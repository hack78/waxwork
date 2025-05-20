<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'name',
        'label',
        'type',
        'required',
        'placeholder',
        'default_value',
        'options',
        'validation_rules',
        'order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required' => 'boolean',
        'status' => 'boolean',
        'options' => 'array',
        'validation_rules' => 'array',
    ];

    /**
     * 获取字段所属的表单
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * 获取字段的文件上传
     */
    public function files()
    {
        return $this->hasMany(FormDataFile::class, 'field_id');
    }

    /**
     * 检查字段是否是文本类型
     */
    public function isText()
    {
        return $this->type === 'text';
    }

    /**
     * 检查字段是否是文本域类型
     */
    public function isTextarea()
    {
        return $this->type === 'textarea';
    }

    /**
     * 检查字段是否是单选类型
     */
    public function isRadio()
    {
        return $this->type === 'radio';
    }

    /**
     * 检查字段是否是多选类型
     */
    public function isCheckbox()
    {
        return $this->type === 'checkbox';
    }

    /**
     * 检查字段是否是下拉选择类型
     */
    public function isSelect()
    {
        return $this->type === 'select';
    }

    /**
     * 检查字段是否是日期类型
     */
    public function isDate()
    {
        return $this->type === 'date';
    }

    /**
     * 检查字段是否是文件类型
     */
    public function isFile()
    {
        return $this->type === 'file';
    }

    /**
     * 获取字段的选项列表
     */
    public function getOptionsList()
    {
        if (!$this->options) {
            return [];
        }

        return collect($this->options)->map(function ($option) {
            return [
                'label' => $option['label'] ?? $option['value'] ?? '',
                'value' => $option['value'] ?? '',
            ];
        })->toArray();
    }

    /**
     * 验证字段值
     */
    public function validateValue($value)
    {
        $rules = ['value' => []];
        
        // 必填验证
        if ($this->required) {
            $rules['value'][] = 'required';
        } else {
            $rules['value'][] = 'nullable';
        }

        // 根据字段类型添加验证规则
        switch ($this->type) {
            case 'text':
                $rules['value'][] = 'string';
                break;
            case 'textarea':
                $rules['value'][] = 'string';
                break;
            case 'radio':
                $rules['value'][] = 'string';
                if ($this->options) {
                    $options = collect($this->options)->pluck('value')->toArray();
                    $rules['value'][] = 'in:' . implode(',', $options);
                }
                break;
            case 'checkbox':
                $rules['value'][] = 'array';
                if ($this->options) {
                    $options = collect($this->options)->pluck('value')->toArray();
                    $rules['value.*'][] = 'in:' . implode(',', $options);
                }
                break;
            case 'select':
                if ($this->options) {
                    $options = collect($this->options)->pluck('value')->toArray();
                    $rules['value'][] = 'in:' . implode(',', $options);
                }
                break;
            case 'date':
                $rules['value'][] = 'date';
                break;
            case 'file':
                $rules['value'][] = 'file';
                break;
        }

        // 添加自定义验证规则
        if ($this->validation_rules) {
            $rules['value'] = array_merge($rules['value'], $this->validation_rules);
        }

        return validator(['value' => $value], $rules);
    }

    /**
     * 格式化字段值用于显示
     */
    public function formatValue($value)
    {
        if ($value === null) {
            return '';
        }

        switch ($this->type) {
            case 'checkbox':
                if (is_array($value)) {
                    $options = collect($this->options)->keyBy('value');
                    return collect($value)->map(function ($val) use ($options) {
                        return $options[$val]['label'] ?? $val;
                    })->implode(', ');
                }
                return $value;
            case 'radio':
            case 'select':
                $options = collect($this->options)->keyBy('value');
                return $options[$value]['label'] ?? $value;
            case 'date':
                return date('Y-m-d', strtotime($value));
            case 'file':
                return is_array($value) ? count($value) . '个文件' : '1个文件';
            default:
                return $value;
        }
    }
}