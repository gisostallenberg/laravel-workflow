<?php

namespace Tests;

use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Storage;
use ZeroDaHero\LaravelWorkflow\Commands\WorkflowDumpCommand;

class WorkflowDumpCommandTest extends BaseWorkflowTestCase
{
    public function testShouldThrowExceptionForUndefinedWorkflow()
    {
        $command = $this->getMock(workflow: 'fake');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow fake is not configured.');

        $command->handle();
    }

    public function testShouldThrowExceptionForUndefinedClass()
    {
        $command = $this->getMock(class: 'Tests\Fixtures\FakeObject');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow straight has no support for' .
            ' class Tests\Fixtures\FakeObject. Please specify a valid support' .
            ' class with the --class option.');

        $command->handle();
    }

    public function testWorkflowCommand()
    {
        $optionalPath = '/my/path';
        $disk = 'public';

        Storage::fake($disk);

        if (Storage::disk($disk)->exists($optionalPath . '/straight.png')) {
            Storage::disk($disk)->delete($optionalPath . '/straight.png');
        }

        $command = $this->getMock(disk: $disk, path: $optionalPath);
        $command->handle();

        Storage::disk($disk)->assertExists($optionalPath . '/straight.png');
    }

    public function testWorkflowCommandWithMetadata()
    {
        $disk = 'public';

        Storage::fake($disk);

        $command = $this->getMock(
            disk: $disk,
            format: 'svg',
            withMetadata: true,
        );

        $command->handle();

        Storage::disk($disk)->assertExists('straight.svg');
        $svg_file = Storage::disk($disk)->get('straight.svg');
        $this->assertStringContainsString('metadata_place', $svg_file);
        $this->assertStringContainsString('metadata_exists', $svg_file);
    }

    public function testWorkflowCommandWithoutMetadata()
    {
        $disk = 'public';

        Storage::fake($disk);

        $command = $this->getMock(
            disk: $disk,
            format: 'svg',
            withMetadata: false,
        );

        $command->handle();

        Storage::disk($disk)->assertExists('straight.svg');
        $svg_file = Storage::disk($disk)->get('straight.svg');
        $this->assertStringContainsString('metadata_place', $svg_file);
        $this->assertStringNotContainsString('metadata_exists', $svg_file);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']['workflow'] = [
            'straight' => [
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => [
                    'a',
                    'b',
                    'c',
                    'metadata_place' => [
                        'metadata' => [
                            'metadata_exists' => true,
                        ],
                    ],
                ],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ],
                ],
            ],
        ];
    }

    private function getMock(
        string $workflow = 'straight',
        string $format = 'png',
        string $class = 'Tests\Fixtures\TestObject',
        string $disk = 'local',
        string $path = '/',
        bool $withMetadata = false,
    ): MockInterface {
        return Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn($workflow)
            ->shouldReceive('option')
            ->with('format')
            ->andReturn($format)
            ->shouldReceive('option')
            ->with('class')
            ->andReturn($class)
            ->shouldReceive('option')
            ->with('disk')
            ->andReturn($disk)
            ->shouldReceive('option')
            ->with('path')
            ->andReturn($path)
            ->shouldReceive('option')
            ->with('with-metadata')
            ->andReturn($withMetadata)
            ->getMock();
    }
}
