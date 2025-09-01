<?php

/**
 * Campo de Abas
 * 
 * Esta classe implementa um campo de formulário que organiza campos em abas,
 * permitindo uma interface mais limpa e organizada para formulários complexos.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToTabs;

class TabsField extends Field
{
    use BelongsToTabs;

    /**
     * Tipo de navegação das abas
     * 
     * @var string
     */
    protected string $navigationType = 'tabs'; // tabs, pills, underline

    /**
     * Se as abas devem ser responsivas
     * 
     * @var bool
     */
    protected bool $responsive = true;

    /**
     * Se deve mostrar indicador de progresso
     * 
     * @var bool
     */
    protected bool $showProgress = false;

    /**
     * Nome do parametro de query para a aba ativa
     * 
     * @var string
     */
    protected string $activeTabParam = 'tab';

    /**
     * Construtor da classe
     * 
     * @param string $key Chave identificadora do campo
     * @param string $label Rótulo do campo
     */
    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type('tabs');
    }


    /**
     * Define o tipo de navegação das abas
     * 
     * @param string $type Tipo: tabs, pills, underline
     * @return static
     */
    public function navigationType(string $type): static
    {
        $this->navigationType = $type;
        return $this;
    }

    /**
     * Define se as abas devem ser responsivas
     * 
     * @param bool $responsive
     * @return static
     */
    public function responsive(bool $responsive = true): static
    {
        $this->responsive = $responsive;
        return $this;
    }

    /**
     * Define se deve mostrar indicador de progresso
     * 
     * @param bool $showProgress
     * @return static
     */
    public function showProgress(bool $showProgress = true): static
    {
        $this->showProgress = $showProgress;
        return $this;
    }

    /**
     * Define a primeira aba como ativa
     * 
     * @return static
     */
    public function firstTabActive(): static
    {
        if (!empty($this->tabs)) {
            $this->tabs[0]->active(true);
        }
        return $this;
    }

    // ==========================================
    // MÉTODOS DE ACESSO (GETTERS)
    // ==========================================



    /**
     * Retorna o tipo de navegação
     * 
     * @return string
     */
    public function getNavigationType(): string
    {
        return $this->navigationType;
    }

    /**
     * Retorna se as abas são responsivas
     * 
     * @return bool
     */
    public function isResponsive(): bool
    {
        return $this->responsive;
    }

    /**
     * Retorna se deve mostrar progresso
     * 
     * @return bool
     */
    public function shouldShowProgress(): bool
    {
        return $this->showProgress;
    }

    // ==========================================
    // MÉTODOS DE PROCESSAMENTO
    // ==========================================


    public function getValue($initialValue = null): mixed
    {
        if ($this->valueCallback) {
            return $this->evaluate($this->valueCallback, ['initialValue' => $initialValue, 'name' => $this->getName()]);
        }
        if ($tabs = $this->getTabs()) {
            foreach ($tabs as $tab) {
                $initialValue =   $tab->getValue($initialValue);
            }
        }
        return  $initialValue;
    }
    /**
     * Converte o campo para array
     * 
     * @return array Representação do campo em array
     */
    public function toArray($model = null): array
    {
        $this->firstTabActive();
        // Processa todas as abas
        $tabsData = $this->getTabsForForm();

        $activeTab = collect($tabsData)->filter(function ($tab) {
            return data_get($tab, 'active');
        })->first();

        return array_merge(parent::toArray($model), [
            'tabs' => $tabsData,
            'navigationType' => $this->navigationType,
            'responsive' => $this->responsive,
            'showProgress' => $this->showProgress,
            'activeTab' =>  request()->get($this->activeTabParam, data_get($activeTab, 'name')),
        ]);
    }
}
