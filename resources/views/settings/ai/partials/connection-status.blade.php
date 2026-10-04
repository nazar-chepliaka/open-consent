@if ($connection->last_test_status === 'success')
    <span class="text-success">Підключення перевірено</span>
@elseif ($connection->last_test_status === 'failed')
    <span class="text-danger">Перевірка не вдалася</span>
@else
    <span class="text-body-secondary">Ще не перевірялося</span>
@endif
@if ($connection->last_tested_at)
    <div class="small text-body-secondary">{{ $connection->last_tested_at->format('Y-m-d H:i') }}</div>
@endif
