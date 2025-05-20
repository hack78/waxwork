<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * 获取配置值（根据类型自动转换）
     */
    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'number':
                return is_numeric($value) ? $value + 0 : $value;
            case 'boolean':
                return (bool)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * 设置配置值（根据类型自动转换）
     */
    public function setValueAttribute($value)
    {
        switch ($this->type) {
            case 'number':
                $this->attributes['value'] = is_numeric($value) ? $value : $value;
                break;
            case 'boolean':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            default:
                $this->attributes['value'] = $value;
        }
    }

    /**
     * 获取配置项
     */
    public static function getConfig($key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * 设置配置项
     */
    public static function setConfig($key, $value, $type = 'string', $name = null, $description = null)
    {
        $config = self::firstOrNew(['key' => $key]);
        $config->name = $name ?? $key;
        $config->value = $value;
        $config->type = $type;
        $config->description = $description;
        return $config->save();
    }

    /**
     * 获取企业微信配置
     */
    public static function getWeworkConfig()
    {
        return [
            'corpid' => self::getConfig('wework_corpid'),
            'secret' => self::getConfig('wework_secret'),
            'agent_id' => self::getConfig('wework_agent_id'),
            'token' => self::getConfig('wework_token'),
            'aes_key' => self::getConfig('wework_aes_key'),
        ];
    }

    /**
     * 设置企业微信配置
     */
    public static function setWeworkConfig($corpid, $secret, $agentId, $token, $aesKey)
    {
        self::setConfig('wework_corpid', $corpid, 'string', '企业微信CorpID', '企业微信企业ID');
        self::setConfig('wework_secret', $secret, 'string', '企业微信Secret', '企业微信应用Secret');
        self::setConfig('wework_agent_id', $agentId, 'number', '企业微信AgentID', '企业微信应用ID');
        self::setConfig('wework_token', $token, 'string', '企业微信Token', '企业微信回调Token');
        self::setConfig('wework_aes_key', $aesKey, 'string', '企业微信AESKey', '企业微信回调EncodingAESKey');
        return true;
    }

    /**
     * 获取系统名称
     */
    public static function getSystemName()
    {
        return self::getConfig('system_name', '企业微信综合管理系统');
    }

    /**
     * 获取系统Logo URL
     */
    public static function getSystemLogo()
    {
        return self::getConfig('system_logo', '/images/logo.png');
    }

    /**
     * 获取系统版本
     */
    public static function getSystemVersion()
    {
        return self::getConfig('system_version', '1.0.0');
    }
}