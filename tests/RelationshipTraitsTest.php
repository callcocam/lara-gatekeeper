<?php

/**
 * Teste simples para os traits de relacionamento
 */

namespace Tests;

use Callcocam\LaraGatekeeper\Core\Concerns\RelationshipManager;
use PHPUnit\Framework\TestCase;

class RelationshipTraitsTest extends TestCase
{
    public function testRelationshipManagerTraitExists()
    {
        $this->assertTrue(trait_exists(RelationshipManager::class));
    }

    public function testRelationshipDetectorTraitExists()
    {
        $this->assertTrue(trait_exists(\Callcocam\LaraGatekeeper\Core\Concerns\RelationshipDetector::class));
    }

    public function testRelationshipFilterTraitExists()
    {
        $this->assertTrue(trait_exists(\Callcocam\LaraGatekeeper\Core\Concerns\RelationshipFilter::class));
    }

    public function testBelongsToOptionsTraitExists()
    {
        $this->assertTrue(trait_exists(\Callcocam\LaraGatekeeper\Core\Concerns\BelongsToOptions::class));
    }
} 