<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class TradeConcurrencyTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tradingtrainer-concurrency-'.Str::uuid().'.sqlite';
        touch($this->databasePath);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $this->databasePath]);
        DB::purge();
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge();
        foreach ([$this->databasePath, $this->databasePath.'-wal', $this->databasePath.'-shm'] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_simultaneous_orders_cannot_spend_the_same_balance_twice(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 200, 'currency' => 'EUR']);
        $workers = [$this->worker($user, (string) Str::uuid()), $this->worker($user, (string) Str::uuid())];
        foreach ($workers as $worker) {
            $worker->start();
        }
        $outcomes = [];
        foreach ($workers as $worker) {
            $worker->wait();
            $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
            $outcomes[] = $worker->getOutput();
        }
        sort($outcomes);
        $this->assertSame(['filled', 'rejected'], $outcomes);
        $this->assertSame(1, $user->paperTrades()->count());
        $this->assertGreaterThanOrEqual(0, (float) $user->wallet->fresh()->balance);
    }

    public function test_simultaneous_retries_fill_only_one_trade(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 200, 'currency' => 'EUR']);
        $key = (string) Str::uuid();
        $workers = [$this->worker($user, $key), $this->worker($user, $key)];
        foreach ($workers as $worker) {
            $worker->start();
        }
        foreach ($workers as $worker) {
            $worker->wait();
            $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
            $this->assertSame('filled', $worker->getOutput());
        }
        $this->assertSame(1, $user->paperTrades()->count());
        $this->assertEquals(1, $user->paperPositions()->value('quantity'));
    }

    private function worker(User $user, string $key): Process
    {
        return new Process([PHP_BINARY, base_path('tests/Support/concurrent-order.php'), $this->databasePath, (string) $user->id, $key], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => $this->databasePath, 'CACHE_STORE' => 'array', 'TRADING_DATA_MODE' => 'simulated']);
    }
}
