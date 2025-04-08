<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */
 namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

 use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class RichTextInput extends Field
{
    protected string $component = 'RichTextInput';
    
    protected ?string $type = 'richtext';
    
    protected array $editorConfig = [];
    
    protected ?int $minHeight = 300;
    
    protected ?string $placeholder = null;
    
    public function config(array $config): static
    {
        $this->editorConfig = array_merge($this->editorConfig, $config);
        return $this;
    }
    
    public function minHeight(int $height): static
    {
        $this->minHeight = $height;
        return $this;
    }
    
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'editorConfig' => $this->editorConfig,
            'minHeight' => $this->minHeight,
            'placeholder' => $this->placeholder,
            'value' => $model ? data_get($model, $this->getName()) : $this->default,
        ]);
    }
}
