<?php

declare(strict_types=1);
/**
 * This file is part of ALAPI.
 *
 * @package  ALAPI\Acme
 * @link     https://www.alapi.cn
 * @license  MIT License
 * @copyright ALAPI <im@alone88.cn>
 */
use ALAPI\Acme\Data\AbstractData;

// Create a concrete implementation class for testing
class TestData extends AbstractData
{
    public function __construct(
        public string $name,
        public int $age,
        public array $tags = []
    ) {
    }
}

class NestedTestData extends AbstractData
{
    public function __construct(
        public TestData $user,
        public string $status
    ) {
    }
}

describe('AbstractData', function () {
    it('can convert to array', function () {
        $data = new TestData('John', 25, ['admin', 'user']);
        $array = $data->toArray();

        expect($array)->toBe([
            'name' => 'John',
            'age' => 25,
            'tags' => ['admin', 'user'],
        ]);
    });

    it('can convert to JSON', function () {
        $data = new TestData('Jane', 30, ['moderator']);
        $json = $data->toJson();

        $expected = json_encode([
            'name' => 'Jane',
            'age' => 30,
            'tags' => ['moderator'],
        ]);

        expect($json)->toBe($expected);
    });

    it('can convert to formatted JSON', function () {
        $data = new TestData('Bob', 35, []);
        $json = $data->toJson(JSON_PRETTY_PRINT);

        expect($json)->toContain("{\n")
            ->and($json)->toContain('    "name": "Bob"')
            ->and($json)->toContain('    "age": 35');
    });

    it('can create object from array', function () {
        $array = [
            'name' => 'Alice',
            'age' => 28,
            'tags' => ['editor', 'writer'],
        ];

        $data = TestData::from($array);

        expect($data)->toBeInstanceOf(TestData::class)
            ->and($data->name)->toBe('Alice')
            ->and($data->age)->toBe(28)
            ->and($data->tags)->toBe(['editor', 'writer']);
    });

    it('can create object from JSON', function () {
        $json = json_encode([
            'name' => 'Charlie',
            'age' => 40,
            'tags' => ['admin'],
        ]);

        $data = TestData::fromJson($json);

        expect($data)->toBeInstanceOf(TestData::class)
            ->and($data->name)->toBe('Charlie')
            ->and($data->age)->toBe(40)
            ->and($data->tags)->toBe(['admin']);
    });

    it('handles nested AbstractData objects', function () {
        $user = new TestData('David', 33, ['user']);
        $nested = new NestedTestData($user, 'active');

        $array = $nested->toArray();

        expect($array)->toBe([
            'user' => [
                'name' => 'David',
                'age' => 33,
                'tags' => ['user'],
            ],
            'status' => 'active',
        ]);
    });

    it('handles AbstractData objects in arrays', function () {
        $user1 = new TestData('Eva', 29, ['admin']);
        $user2 = new TestData('Frank', 31, ['user']);

        $container = new class($user1, $user2) extends AbstractData {
            public function __construct(
                public TestData $primaryUser,
                public TestData $secondaryUser,
                public array $allUsers = []
            ) {
                $this->allUsers = [$primaryUser, $secondaryUser];
            }
        };

        $array = $container->toArray();

        expect($array['allUsers'])->toHaveCount(2)
            ->and($array['allUsers'][0])->toBe([
                'name' => 'Eva',
                'age' => 29,
                'tags' => ['admin'],
            ])
            ->and($array['allUsers'][1])->toBe([
                'name' => 'Frank',
                'age' => 31,
                'tags' => ['user'],
            ]);
    });

    it('handles default parameter values', function () {
        $data = TestData::from(['name' => 'Grace', 'age' => 26]);

        expect($data->name)->toBe('Grace')
            ->and($data->age)->toBe(26)
            ->and($data->tags)->toBe([]); // default value
    });

    it('invalid JSON should throw exception', function () {
        expect(fn () => TestData::fromJson('invalid json'))
            ->toThrow(JsonException::class);
    });
});
