<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Models;

use App\Models\User; 
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Tall\Sluggable\HasSlug;
use Tall\Sluggable\SlugOptions;

class AbstractModel extends Model
{
    use HasUlids, HasSlug;


    protected $guarded = ['id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return SlugOptions
     */
    public function getSlugOptions()
    {
        if (is_string($this->slugTo())) {
            return SlugOptions::create()
                ->generateSlugsFrom($this->slugFrom())
                ->saveSlugsTo($this->slugTo());
        }
    }
}
