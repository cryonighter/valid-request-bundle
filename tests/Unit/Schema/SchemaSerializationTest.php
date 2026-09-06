<?php

namespace Cryonighter\ValidRequestBundle\Tests\Unit\Schema;

use Cryonighter\ValidRequestBundle\Schema\ObjectSchema;
use Cryonighter\ValidRequestBundle\Schema\PropertyNestedItem;
use Cryonighter\ValidRequestBundle\Schema\SchemaFactory;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\CheckoutOrderRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\OrderLineRequest;
use PHPUnit\Framework\TestCase;

class SchemaSerializationTest extends TestCase
{
    private SchemaFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SchemaFactory(sys_get_temp_dir() . '/vr_schema_test', true);
    }

    // =========================================================================
    // Сериализуемость (готовность к persistent-кэшу вроде cache.system)
    // =========================================================================

    /**
     * Схема сложного DTO успешно проходит serialize/unserialize.
     *
     * Сравниваем round-trip с round-trip: после первой сериализации
     * неинициализированные свойства констрейнтов (например, Valid::$groups)
     * нормализуются в null, поэтому "свежая == восстановленная" некорректно,
     * а "восстановленная == дважды восстановленная" — стабильно.
     */
    public function testSchemaSurvivesSerializationRoundTrip(): void
    {
        $schema = $this->factory->create(CheckoutOrderRequest::class);

        $once = unserialize(serialize($schema));
        $twice = unserialize(serialize($once));

        $this->assertInstanceOf(ObjectSchema::class, $once);
        $this->assertEquals($once, $twice);
    }

    /**
     * В сериализованном графе схемы нет объектов рефлексии или замыканий:
     * их наличие сделало бы схему непригодной для persistent-кэша.
     */
    public function testSerializedSchemaContainsNoRuntimeOnlyObjects(): void
    {
        $payload = serialize($this->factory->create(CheckoutOrderRequest::class));

        $this->assertStringNotContainsString('Reflection', $payload);
        $this->assertStringNotContainsString('Closure', $payload);
    }

    // =========================================================================
    // Сохранение структуры после восстановления из кэша
    // =========================================================================

    /**
     * Вложенный объект (deliveryAddress) переживает round-trip:
     * его под-схема и свойства остаются на месте.
     */
    public function testNestedObjectSchemaIsPreserved(): void
    {
        $restored = unserialize(serialize($this->factory->create(CheckoutOrderRequest::class)));

        $this->assertInstanceOf(ObjectSchema::class, $restored);

        $nested = $restored->properties['deliveryAddress']->nested;

        $this->assertInstanceOf(ObjectSchema::class, $nested);
        $this->assertArrayHasKey('postalCode', $nested->properties);
    }

    /**
     * Массив объектов (lines): тип элемента, разрешённый из Assert\All,
     * сохраняется как под-схема после восстановления.
     */
    public function testArrayItemSchemaIsPreserved(): void
    {
        $restored = unserialize(serialize($this->factory->create(CheckoutOrderRequest::class)));

        $this->assertInstanceOf(ObjectSchema::class, $restored);

        $nestedItems = $restored->properties['lines']->nestedItems;

        $this->assertArrayHasKey(0, $nestedItems);
        $this->assertInstanceOf(PropertyNestedItem::class, $nestedItems[0]);

        $schemas = $nestedItems[0]->schemas;

        $this->assertArrayHasKey(OrderLineRequest::class, $schemas);
        $this->assertInstanceOf(ObjectSchema::class, $schemas[OrderLineRequest::class]);
        $this->assertArrayHasKey('articleCode', $schemas[OrderLineRequest::class]->properties);
    }

    /**
     * Nullable вложенный объект (giftWrap) тоже имеет под-схему:
     * структурный спуск строится всегда, независимо от nullability.
     */
    public function testNullableNestedObjectSchemaIsPreserved(): void
    {
        $restored = unserialize(serialize($this->factory->create(CheckoutOrderRequest::class)));

        $this->assertInstanceOf(ObjectSchema::class, $restored);
        $this->assertInstanceOf(ObjectSchema::class, $restored->properties['giftWrap']->nested);
    }
}
