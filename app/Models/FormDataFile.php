<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FormDataFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_data_id',
        'field_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    /**
     * 获取文件所属的表单数据
     */
    public function formData()
    {
        return $this->belongsTo(FormData::class);
    }

    /**
     * 获取文件所属的表单字段
     */
    public function field()
    {
        return $this->belongsTo(FormField::class);
    }

    /**
     * 获取文件的URL
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * 获取文件的大小（格式化后）
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 检查文件是否为图片
     */
    public function isImage()
    {
        return strpos($this->mime_type, 'image/') === 0;
    }

    /**
     * 检查文件是否为PDF
     */
    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * 检查文件是否为文档
     */
    public function isDocument()
    {
        $docTypes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        
        return in_array($this->mime_type, $docTypes);
    }

    /**
     * 获取文件内容
     */
    public function getContents()
    {
        return Storage::get($this->file_path);
    }

    /**
     * 删除文件
     */
    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
        
        return $this->delete();
    }

    /**
     * 从上传文件创建记录
     */
    public static function createFromUpload($file, $formDataId, $fieldId)
    {
        $path = $file->store('form_uploads');
        
        return self::create([
            'form_data_id' => $formDataId,
            'field_id' => $fieldId,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}