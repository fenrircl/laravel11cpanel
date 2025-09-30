<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilesRegistry extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files_registry';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // La tabla solo tiene created_at, no updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'real_id',
        'path',
        'file_name',
        'mime_type',
        'size',
        'migrated',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'size' => 'integer',
        'model_id' => 'integer',
        'real_id' => 'integer',
        'migrated' => 'boolean',
    ];

    /**
     * Accessor para file_name para asegurar UTF-8 válido
     */
    public function getFileNameAttribute($value)
    {
        if (!$value) return $value;
        
        // Asegurar que el valor sea UTF-8 válido
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        
        // Limpiar caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        return trim($value);
    }

    /**
     * Mutator para file_name para asegurar UTF-8 válido al guardar
     */
    public function setFileNameAttribute($value)
    {
        if (!$value) {
            $this->attributes['file_name'] = $value;
            return;
        }
        
        // Asegurar que el valor sea UTF-8 válido
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        
        // Limpiar caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        $this->attributes['file_name'] = trim($value);
    }

    /**
     * Accessor para path para asegurar UTF-8 válido
     */
    public function getPathAttribute($value)
    {
        if (!$value) return $value;
        
        // Asegurar que el valor sea UTF-8 válido
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        
        // Limpiar caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        return trim($value);
    }

    /**
     * Mutator para path para asegurar UTF-8 válido al guardar
     */
    public function setPathAttribute($value)
    {
        if (!$value) {
            $this->attributes['path'] = $value;
            return;
        }
        
        // Asegurar que el valor sea UTF-8 válido
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        
        // Limpiar caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        $this->attributes['path'] = trim($value);
    }
}
