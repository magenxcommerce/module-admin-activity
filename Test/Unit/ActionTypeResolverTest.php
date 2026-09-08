<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */
declare(strict_types=1);

namespace Magenx\AdminActivity\Test\Unit;

use Magenx\AdminActivity\Model\Activity\ActionType;
use Magenx\AdminActivity\Model\Activity\ActionTypeResolver;
use PHPUnit\Framework\TestCase;

class ActionTypeResolverTest extends TestCase
{
    private ActionTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ActionTypeResolver();
    }

    public function testSaveOfANewEntityIsAnAdd(): void
    {
        self::assertSame(ActionType::ADD, $this->resolver->resolve('catalog_product_save', 'POST', true));
    }

    public function testSaveOfAnExistingEntityIsAnEdit(): void
    {
        self::assertSame(ActionType::EDIT, $this->resolver->resolve('catalog_product_save', 'POST', false));
    }

    public function testDeleteIsRecognisedDespiteBeingAPost(): void
    {
        // Delete and edit both arrive as POST; only the action name separates
        // them, which is why the resolver dispatches on that first.
        self::assertSame(ActionType::DELETE, $this->resolver->resolve('cms_page_delete', 'POST'));
    }

    public function testMassDeleteIsADeleteNotAMassUpdate(): void
    {
        // Ordering matters: filed as mass_update, a bulk delete would vanish
        // from a "what was deleted" query.
        self::assertSame(ActionType::DELETE, $this->resolver->resolve('cms_page_massdelete', 'POST'));
    }

    public function testOtherMassActionsAreMassUpdates(): void
    {
        self::assertSame(ActionType::MASS_UPDATE, $this->resolver->resolve('catalog_product_massstatus', 'POST'));
        self::assertSame(ActionType::MASS_UPDATE, $this->resolver->resolve('customer_index_massassigngroup', 'POST'));
    }

    public function testPrintIsRecognised(): void
    {
        self::assertSame(
            ActionType::PRINT_ACTION,
            $this->resolver->resolve('sales_order_invoice_print', 'GET')
        );
    }

    public function testEditScreenIsAView(): void
    {
        self::assertSame(ActionType::VIEW, $this->resolver->resolve('catalog_product_edit', 'GET'));
        self::assertSame(ActionType::VIEW, $this->resolver->resolve('sales_order_view', 'GET'));
    }

    public function testInlineEditIsAWriteNotAView(): void
    {
        self::assertSame(ActionType::EDIT, $this->resolver->resolve('cms_page_inlineedit', 'POST', false));
    }

    public function testUnknownActionFallsBackToTheHttpMethod(): void
    {
        self::assertSame(ActionType::VIEW, $this->resolver->resolve('some_custom_thing', 'GET'));
        self::assertSame(ActionType::EDIT, $this->resolver->resolve('some_custom_thing', 'POST'));
    }

    public function testEmptyActionNameDoesNotBlowUp(): void
    {
        self::assertSame(ActionType::VIEW, $this->resolver->resolve('', 'GET'));
    }
}
