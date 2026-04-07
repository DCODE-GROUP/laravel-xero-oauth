<?php

use Dcodegroup\LaravelXeroOauth\BaseXeroService;
use Mockery\MockInterface;
use XeroPHP\Application;

if (! class_exists('FakeXeroQueryResult')) {
    class FakeXeroQueryResult
    {
        public array $whereCalls = [];

        public function __construct(private array $records = []) {}

        public function where(string $property, mixed $value): self
        {
            $this->whereCalls[] = [$property, $value];

            return $this;
        }

        public function first(): mixed
        {
            return $this->records[0] ?? null;
        }

        public function execute(): array
        {
            return $this->records;
        }
    }
}

if (! class_exists('FakeWritableModel')) {
    class FakeWritableModel
    {
        public array $values = [];

        public function __construct(public Application $application) {}

        public function __call(string $name, array $arguments)
        {
            if (str_starts_with($name, 'set')) {
                $this->values[$name] = $arguments[0] ?? null;
            }

            if (str_starts_with($name, 'add')) {
                $this->values[$name][] = $arguments[0] ?? null;
            }

            return $this;
        }

        public function setDirty(string $attribute): void {}

        public function save(): void {}
    }
}

if (! class_exists('FakeFailingWritableModel')) {
    class FakeFailingWritableModel extends FakeWritableModel
    {
        public function save(): void
        {
            throw new Exception('save failed');
        }
    }
}

it('loads and returns a collection when getting a model list', function () {
    $query = new FakeXeroQueryResult([
        (object) ['name' => 'one'],
        (object) ['name' => 'two'],
    ]);

    $this->mock(Application::class, function (MockInterface $mock) use ($query) {
        $mock->shouldReceive('load')->once()->andReturn($query);
    });

    $service = new BaseXeroService(app(Application::class));
    $result = $service->getModel('SomeModel');

    expect($result)->toHaveCount(2);
});

it('applies where filters and returns first record when searching', function () {
    $query = new FakeXeroQueryResult([
        (object) ['id' => 'record_1'],
    ]);

    $this->mock(Application::class, function (MockInterface $mock) use ($query) {
        $mock->shouldReceive('load')->once()->andReturn($query);
    });

    $service = new BaseXeroService(app(Application::class));
    $result = $service->searchModel('SomeModel', ['Status' => 'ACTIVE']);

    expect($query->whereCalls)->toBe([['Status', 'ACTIVE']]);
    expect($result->id)->toBe('record_1');
});

it('returns exception object when save model fails', function () {
    $service = new BaseXeroService(app(Application::class));

    $result = $service->saveModel(FakeFailingWritableModel::class, ['Name' => 'Example']);

    expect($result)->toBeInstanceOf(Exception::class);
    expect($result->getMessage())->toBe('save failed');
});

