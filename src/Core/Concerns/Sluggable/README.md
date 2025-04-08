#TAL SLUGGABLE TABLE

#ALTERANDO OS CAMPOS PARA GERAR A SLUG

#CONFIGURAÇÕES PARA A MODEL
```

use Tall\Sluggable\SlugOptions;
use Tall\Sluggable\HasSlug;

adicionar a trait
use  HasSlug;

```

#CAMPO  GERAR A SLUG - Alterar somente se o nome for diferente de (slug)
```
    /**
    * @return string
    */
    protected function slugTo()
    {
        return "slug";
    }
```

#CAMPO BASE PARA GERAR A SLUG - Alterar somente se o nome for diferente de (name)
```
    /**
    * @return string
    */
    protected function slugFrom()
    {
        return 'name';
    }

```



#OPTIONS
```
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
```