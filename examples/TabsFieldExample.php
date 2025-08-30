<?php

/**
 * Exemplo de uso do TabsField
 * 
 * Este arquivo demonstra como usar o sistema de abas
 * para organizar campos em formulários complexos.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Callcocam\LaraGatekeeper\Core\Support\Form\Fields\TabsField;
use Callcocam\LaraGatekeeper\Core\Support\Form\Fields\RelationshipField;

// ==========================================
// EXEMPLO 1: Configuração Básica
// ==========================================

$basicTabs = TabsField::make('content', 'Conteúdo do Produto')
    ->tab('Informações Básicas', function($tab) {
        // $tab->field(TextField::make('name', 'Nome do Produto')->required())
        //     ->field(TextField::make('sku', 'SKU')->required())
        //     ->field(SelectField::make('category_id', 'Categoria')
        //         ->options(['1' => 'Eletrônicos', '2' => 'Roupas', '3' => 'Livros']));
    })
    ->tab('Descrição', function($tab) {
        // $tab->field(TextareaField::make('description', 'Descrição')->rows(5))
        //     ->field(TextareaField::make('short_description', 'Descrição Curta')->rows(3));
    })
    ->tab('Preços', function($tab) {
        // $tab->field(TextField::make('price', 'Preço')->type('number'))
        //     ->field(TextField::make('cost_price', 'Preço de Custo')->type('number'))
        //     ->field(TextField::make('sale_price', 'Preço Promocional')->type('number'));
    })
    ->firstTabActive();

// ==========================================
// EXEMPLO 2: Configuração Avançada
// ==========================================

$advancedTabs = TabsField::make('advanced_content', 'Conteúdo Avançado')
    ->navigationType('pills')
    ->responsive()
    ->showProgress()
    ->tab('Dados Principais', function($tab) {
        $tab->icon('user')
            ->active();
        // $tab->field(TextField::make('title', 'Título')->required())
        //     ->field(TextField::make('slug', 'Slug')->required());
    })
    ->tab('Relacionamentos', function($tab) {
        $tab->icon('link');
        // $tab->field(HasManyField::make('tags', 'Tags')
        //     ->relationship('tags', 'name', 'id')
        //     ->allowAdd()
        //     ->allowRemove());
    })
    ->tab('Configurações', function($tab) {
        $tab->icon('settings');
        // $tab->field(SelectField::make('status', 'Status')
        //     ->options(['draft' => 'Rascunho', 'published' => 'Publicado', 'archived' => 'Arquivado']))
        //     ->field(TextField::make('order', 'Ordem')->type('number'));
    });

// ==========================================
// EXEMPLO 3: Configuração em Array
// ==========================================

$arrayTabs = TabsField::make('array_content', 'Conteúdo em Array')
    ->tabs([
        [
            'name' => 'general',
            'label' => 'Geral',
            'icon' => 'info',
            'active' => true,
            'fields' => []
            // 'fields' => [
            //     TextField::make('name', 'Nome'),
            //     TextField::make('email', 'Email')
            // ]
        ],
        [
            'name' => 'details',
            'label' => 'Detalhes',
            'icon' => 'file-text',
            'fields' => []
            // 'fields' => [
            //     TextareaField::make('bio', 'Biografia'),
            //     SelectField::make('role', 'Função')
            //         ->options(['admin' => 'Administrador', 'user' => 'Usuário'])
            // ]
        ]
    ]);

// ==========================================
// EXEMPLO 4: Abas Condicionais
// ==========================================

$conditionalTabs = TabsField::make('conditional_content', 'Conteúdo Condicional')
    ->tab('Informações Básicas', function($tab) {
        // $tab->field(TextField::make('name', 'Nome')->required())
        //     ->field(SelectField::make('type', 'Tipo')
        //         ->options(['individual' => 'Individual', 'company' => 'Empresa']));
    })
    ->tab('Dados da Empresa', function($tab) {
        $tab->icon('building');
        // $tab->field(TextField::make('company_name', 'Nome da Empresa'))
        //     ->field(TextField::make('cnpj', 'CNPJ'))
        //     ->field(TextField::make('phone', 'Telefone'));
    })
    ->tab('Dados Pessoais', function($tab) {
        $tab->icon('user');
        // $tab->field(TextField::make('cpf', 'CPF'))
        //     ->field(TextField::make('birth_date', 'Data de Nascimento')->type('date'))
        //     ->field(TextField::make('personal_phone', 'Telefone Pessoal'));
    });

// ==========================================
// EXEMPLO 5: Abas com Validação
// ==========================================

$validationTabs = TabsField::make('validation_content', 'Conteúdo com Validação')
    ->tab('Dados Obrigatórios', function($tab) {
        $tab->icon('alert-circle');
        // $tab->field(TextField::make('required_field', 'Campo Obrigatório')->required())
        //     ->field(TextField::make('email_field', 'Email')->type('email')->required());
    })
    ->tab('Dados Opcionais', function($tab) {
        $tab->icon('check-circle');
        // $tab->field(TextField::make('optional_field', 'Campo Opcional'))
        //     ->field(TextareaField::make('notes', 'Observações')->rows(3));
    })
    ->tab('Configurações Avançadas', function($tab) {
        $tab->icon('settings');
        // $tab->field(SelectField::make('theme', 'Tema')
        //     ->options(['light' => 'Claro', 'dark' => 'Escuro', 'auto' => 'Automático']))
        //     ->field(TextField::make('timezone', 'Fuso Horário')->default('UTC'));
    });

// ==========================================
// COMO USAR NO FORMULÁRIO
// ==========================================

/*
// Em um Resource ou Form:
public function fields(): array
{
    return [
        $basicTabs,
        $advancedTabs,
        // ... outros campos
    ];
}

// Ou diretamente:
TabsField::make('content', 'Conteúdo')
    ->tab('Aba 1', function($tab) {
        $tab->field(TextField::make('field1', 'Campo 1'));
    })
    ->tab('Aba 2', function($tab) {
        $tab->field(TextField::make('field2', 'Campo 2'));
    })
*/ 