<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class AllConstraintsTest extends AbstractWebTestCase
{
    // =========================================================================
    // Кейс 1: один Assert\All, внутри два Assert\Type — каждый со своей группой.
    // Активной становится только ветка, чья группа совпадает с группой запроса.
    // =========================================================================

    /**
     * Группа Wizards: активен Assert\Type(Wizard::class).
     * Валидные данные собираются в массив инстансов Wizard.
     */
    public function testTypeGroupsWizardsBuildsWizards(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/type-groups/wizards',
            [
                'party' => [
                    ['name' => 'Gandalf', 'spellPower' => 90],
                    ['name' => 'Merlin', 'spellPower' => 80],
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertArrayHasKey('party', $data);
        $this->assertCount(2, $data['party']);

        $this->assertSame('Wizard', $data['party'][0]['class']);
        $this->assertSame('Gandalf', $data['party'][0]['name']);
        $this->assertSame(90, $data['party'][0]['spellPower']);

        $this->assertSame('Wizard', $data['party'][1]['class']);
        $this->assertSame('Merlin', $data['party'][1]['name']);
        $this->assertSame(80, $data['party'][1]['spellPower']);
    }

    /**
     * Группа Warriors: активен Assert\Type(Warrior::class).
     * Валидные данные собираются в массив инстансов Warrior.
     */
    public function testTypeGroupsWarriorsBuildsWarriors(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/type-groups/warriors',
            [
                'party' => [
                    ['name' => 'Conan', 'strength' => 42],
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertArrayHasKey('party', $data);
        $this->assertCount(1, $data['party']);

        $hero = $data['party'][0];

        $this->assertSame('Warrior', $hero['class']);
        $this->assertSame('Conan', $hero['name']);
        $this->assertSame(42, $hero['strength']);
    }

    /**
     * Группа Wizards с невалидным внутренним полем:
     * срабатывает ошибка валидации, spellPower вне диапазона [1..100].
     */
    public function testTypeGroupsWizardsInvalidElementReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/type-groups/wizards',
            [
                'party' => [
                    ['name' => 'Gandalf', 'spellPower' => 999],
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('party[0].spellPower', $paths);
    }

    // =========================================================================
    // Кейс 2: два Assert\All (каждый в своей группе), внутри Assert\Type без группы.
    // Assert\Type наследует группу от родительского Assert\All.
    // =========================================================================

    /**
     * Группа Wizards: активен только первый Assert\All с Type(Wizard::class).
     * Валидные данные собираются в массив инстансов Wizard.
     */
    public function testGroupedAllWizardsBuildsWizards(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/grouped/wizards',
            [
                'party' => [
                    ['name' => 'Gandalf', 'spellPower' => 90],
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertArrayHasKey('party', $data);
        $this->assertCount(1, $data['party']);

        $hero = $data['party'][0];

        $this->assertSame('Wizard', $hero['class']);
        $this->assertSame('Gandalf', $hero['name']);
        $this->assertSame(90, $hero['spellPower']);
    }

    /**
     * Группа Warriors: активен только второй Assert\All с Type(Warrior::class).
     * Валидные данные собираются в массив инстансов Warrior.
     */
    public function testGroupedAllWarriorsBuildsWarriors(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/grouped/warriors',
            [
                'party' => [
                    ['name' => 'Conan', 'strength' => 42],
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertArrayHasKey('party', $data);
        $this->assertCount(1, $data['party']);

        $hero = $data['party'][0];

        $this->assertSame('Warrior', $hero['class']);
        $this->assertSame('Conan', $hero['name']);
        $this->assertSame(42, $hero['strength']);
    }

    /**
     * Группа Wizards: невалидный внутренний элемент вызывает violation.
     * name слишком короткое.
     */
    public function testGroupedAllWizardsInvalidElementReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/grouped/wizards',
            [
                'party' => [
                    ['name' => 'G', 'spellPower' => 50],
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('party[0].name', $paths);
    }

    // =========================================================================
    // Кейс 3: комбинированный — 2 Assert\All + 2 Assert\Type, всего 4 группы.
    // Ветка активна только если в запросе присутствует согласованная пара групп.
    // =========================================================================

    /**
     * Согласованная пара ['AllWizards', 'TypeWizard']: активна ветка магов.
     * Валидные данные собираются в массив инстансов Wizard.
     */
    public function testCombinedWizardsMatchedBuildsWizards(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/combined/wizards-matched',
            [
                'party' => [
                    ['name' => 'Gandalf', 'spellPower' => 90],
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertArrayHasKey('party', $data);
        $this->assertCount(1, $data['party']);

        $hero = $data['party'][0];

        $this->assertSame('Wizard', $hero['class']);
        $this->assertSame('Gandalf', $hero['name']);
        $this->assertSame(90, $hero['spellPower']);
    }

    /**
     * Согласованная пара ['AllWarriors', 'TypeWarrior']: активна ветка воинов.
     * Валидные данные собираются в массив инстансов Warrior.
     */
    public function testCombinedWarriorsMatchedBuildsWarriors(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/all/combined/warriors-matched',
            [
                'party' => [
                    ['name' => 'Conan', 'strength' => 42],
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertArrayHasKey('party', $data);
        $this->assertCount(1, $data['party']);

        $hero = $data['party'][0];

        $this->assertSame('Warrior', $hero['class']);
        $this->assertSame('Conan', $hero['name']);
        $this->assertSame(42, $hero['strength']);
    }

    // =========================================================================
    // Кейс 4: вырожденный — Assert\All внутри Assert\All, два Assert\Type с группами.
    // Группированный массив (массив массивов). Логика групп как в кейсе 1.
    // =========================================================================

//    /**
//     * Группа Wizards: активен вложенный Assert\Type(Wizard::class).
//     * Группированный массив собирается в массивы инстансов Wizard.
//     */
//    public function testNestedAllWizardsBuildsWizards(): void
//    {
//        $browser = $this->createBrowser();
//        $browser->request(
//            'POST',
//            '/all/nested/wizards',
//            [
//                'squads' => [
//                    [
//                        ['name' => 'Gandalf', 'spellPower' => 90],
//                        ['name' => 'Merlin', 'spellPower' => 80],
//                    ],
//                    [
//                        ['name' => 'Morgana', 'spellPower' => 70],
//                    ],
//                ],
//            ],
//        );
//
//        $this->assertResponseIsSuccessful();
//
//        $data = $this->getJsonResponse($browser);
//
//        $this->assertCount(2, $data['squads']);
//        $this->assertCount(2, $data['squads'][0]);
//        $this->assertSame('Wizard', $data['squads'][0][0]['class']);
//        $this->assertSame('Gandalf', $data['squads'][0][0]['name']);
//        $this->assertSame('Wizard', $data['squads'][1][0]['class']);
//    }
//
//    /**
//     * Группа Warriors: активен вложенный Assert\Type(Warrior::class).
//     * Группированный массив собирается в массивы инстансов Warrior.
//     */
//    public function testNestedAllWarriorsBuildsWarriors(): void
//    {
//        $browser = $this->createBrowser();
//        $browser->request(
//            'POST',
//            '/all/nested/warriors',
//            [
//                'squads' => [
//                    [
//                        ['name' => 'Conan', 'strength' => 42],
//                    ],
//                ],
//            ],
//        );
//
//        $this->assertResponseIsSuccessful();
//
//        $data = $this->getJsonResponse($browser);
//
//        $this->assertCount(1, $data['squads']);
//        $this->assertSame('Warrior', $data['squads'][0][0]['class']);
//        $this->assertSame('Conan', $data['squads'][0][0]['name']);
//    }
//
//    /**
//     * Группа Wizards: невалидный элемент внутри вложенного массива вызывает violation.
//     * spellPower вне допустимого диапазона.
//     */
//    public function testNestedAllWizardsInvalidElementReceivesViolation(): void
//    {
//        $browser = $this->createBrowser();
//        $browser->request(
//            'POST',
//            '/all/nested/wizards',
//            [
//                'squads' => [
//                    [
//                        ['name' => 'Gandalf', 'spellPower' => 0],
//                    ],
//                ],
//            ],
//        );
//
//        $this->assertResponseStatusCodeSame(422);
//
//        $data = $this->getJsonResponse($browser);
//
//        $paths = array_column($data['errors'], 'property');
//
//        $this->assertNotEmpty($paths);
//    }
}
