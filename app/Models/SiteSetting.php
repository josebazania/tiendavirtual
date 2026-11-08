<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['site_title', 'seo_description', 'logo_path', 'favicon_path', 'smtp_host', 'smtp_port', 'smtp_scheme', 'smtp_username', 'smtp_password', 'mail_from_address', 'mail_from_name', 'mercadopago_access_token', 'mercadopago_webhook_secret'];

    protected $hidden = ['smtp_password', 'mercadopago_access_token', 'mercadopago_webhook_secret'];

    protected function casts(): array
    {
        return ['smtp_password' => 'encrypted', 'mercadopago_access_token' => 'encrypted', 'mercadopago_webhook_secret' => 'encrypted'];
    }

    public static function current(): self
    {
        return static::find(1) ?? new static(['site_title' => 'Aula', 'smtp_scheme' => 'smtp']);
    }

    public function configureMail(): bool
    {
        if (! $this->smtp_host || ! $this->smtp_port || ! $this->mail_from_address || ! $this->mail_from_name) {
            return false;
        }
        config(['mail.default' => 'smtp', 'mail.mailers.smtp' => ['transport' => 'smtp', 'scheme' => $this->smtp_scheme, 'host' => $this->smtp_host, 'port' => $this->smtp_port, 'username' => $this->smtp_username, 'password' => $this->smtp_password, 'timeout' => 15, 'require_tls' => $this->smtp_scheme === 'smtp'], 'mail.from.address' => $this->mail_from_address, 'mail.from.name' => $this->mail_from_name]);
        app('mail.manager')->purge('smtp');

        return true;
    }
}
