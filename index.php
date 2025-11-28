<?php
declare(strict_types=1);

$jsonFile = __DIR__ . '/data.json';
if (!file_exists($jsonFile)) {
    http_response_code(500);
    echo 'Файл data.json не найден';
    exit;
}
$raw = file_get_contents($jsonFile);
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(500);
    echo 'Некорректный JSON';
    exit;
}

// Ожидаемые поля
$columns = [
    'group' => 'Номер группы',
    'index' => 'Порядковый номер',
    'fio'   => 'ФИО',
    'ide'   => 'IDE'
];

// Экранирование
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Функция для форматирования значения
function formatValue($value): string {
    if ($value === null || $value === '') {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Определяем режим работы
$isExport = isset($_GET['export']) && $_GET['export'] === 'pdf';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $isExport ? 'Экспорт в PDF' : 'Студенты гр. ИС-235.1' ?></title>
  <style>
    <?php if ($isExport): ?>
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 15mm; 
                font-size: 11pt; 
                font-family: 'Times New Roman', serif;
                color: #000;
                background: white !important;
            }
            .container { max-width: none !important; }
            h1 { 
                text-align: center; 
                color: #000 !important;
                margin-bottom: 20px;
                font-size: 16pt;
            }
            table { 
                width: 100%; 
                border-collapse: collapse;
                margin: 15px 0;
                page-break-inside: auto;
            }
            th, td { 
                border: 1pt solid #000 !important; 
                padding: 8pt; 
                text-align: left;
                background: white !important;
                color: #000 !important;
            }
            th { 
                background-color: #f0f0f0 !important;
                font-weight: bold;
            }
            .footer { 
                margin-top: 20mm;
                text-align: center;
                font-size: 9pt;
                color: #666;
            }
            tr { page-break-inside: avoid; }
        }
        body.print-mode {
            background: white;
            margin: 15mm;
        }
    <?php endif; ?>
    
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --secondary: #7209b7;
        --accent: #f72585;
        --light: #f8f9fa;
        --dark: #212529;
        --success: #4cc9f0;
        --border: #dee2e6;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        color: var(--dark);
        line-height: 1.6;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
        position: relative;
    }
    
    .header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: cover;
    }
    
    .header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .header p {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
    }
    
    .content {
        padding: 30px;
    }
    
    .stats {
        background: var(--light);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        border-left: 4px solid var(--primary);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: var(--dark);
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        background: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
    
    .toolbar {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: var(--primary);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }
    
    .btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn-secondary {
        background: var(--secondary);
    }
    
    .btn-secondary:hover {
        background: #5a08a0;
    }
    
    .btn-print {
        background: var(--success);
    }
    
    .btn-print:hover {
        background: #3ab0d6;
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    thead {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }
    
    th {
        padding: 16px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
        transition: background-color 0.2s ease;
    }
    
    tbody tr {
        transition: all 0.3s ease;
    }
    
    tbody tr:hover {
        background: #f8f9ff;
        transform: scale(1.01);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    tbody tr:last-child td {
        border-bottom: none;
    }
    
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
        font-size: 1.1rem;
    }
    
    .no-data .icon {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .footer {
        background: var(--light);
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-size: 0.9rem;
        border-top: 1px solid var(--border);
    }
    
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        
        .header {
            padding: 20px;
        }
        
        .header h1 {
            font-size: 2rem;
        }
        
        .content {
            padding: 20px;
        }
        
        .stats {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .toolbar {
            flex-direction: column;
        }
        
        .btn {
            justify-content: center;
        }
        
        th, td {
            padding: 12px 8px;
            font-size: 14px;
        }
        
        .table-container {
            overflow-x: auto;
        }
    }
  </style>
</head>
<body class="<?= $isExport ? 'print-mode' : '' ?>">
  <?php if ($isExport): ?>
    <div class="no-print" style="text-align: center; margin: 20px;">
        <button class="btn btn-print" onclick="window.print()">
            <span>🖨️</span> Печать / Сохранить как PDF
        </button>
        <a class="btn btn-secondary" href="?">
            <span>←</span> Назад к таблице
        </a>
    </div>

    <div class="container">
        <div class="header">
            <h1>Список студентов</h1>
            <p>Группа ИС-235.1 - Информация о студентах и используемых IDE</p>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div>Всего студентов: <strong><?= count($data) ?></strong></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📅</div>
                    <div>Сгенерировано: <strong><?= date('d.m.Y H:i:s') ?></strong></div>
                </div>
            </div>

            <?php if (empty($data)): ?>
                <div class="no-data">
                    <div class="icon">📭</div>
                    <p>Данных нет</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Группа</th>
                                <th>Порядковый номер</th>
                                <th>ФИО</th>
                                <th>IDE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?= formatValue($row['group'] ?? '') ?></td>
                                <td><?= formatValue($row['index'] ?? '') ?></td>
                                <td><?= formatValue($row['fio'] ?? '') ?></td>
                                <td><?= formatValue($row['ide'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>Отчёт сгенерирован автоматически • Группа ИС-235.1</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>

  <?php else: ?>
    <div class="container">
        <div class="header">
            <h1>Список студентов</h1>
            <p>Группа ИС-235.1 - Информация о студентах и используемых IDE</p>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div>Всего студентов: <strong><?= count($data) ?></strong></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💻</div>
                    <div>Доступные IDE: <strong><?= count(array_unique(array_column($data, 'ide'))) ?></strong></div>
                </div>
            </div>

            <div class="toolbar">
                <a class="btn" href="?export=pdf">
                    <span>📄</span> Экспорт в PDF
                </a>
                <button class="btn btn-secondary" onclick="location.reload()">
                    <span>🔄</span> Обновить данные
                </button>
            </div>

            <?php if (empty($data)): ?>
                <div class="no-data">
                    <div class="icon">📭</div>
                    <p>Нет данных для отображения</p>
                    <p style="margin-top: 10px; font-size: 0.9rem;">Проверьте файл data.json</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($columns as $key => $title): ?>
                                    <th><?= h($title) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <td><?= formatValue($row['group'] ?? '') ?></td>
                                    <td><?= formatValue($row['index'] ?? '') ?></td>
                                    <td><?= formatValue($row['fio'] ?? '') ?></td>
                                    <td><?= formatValue($row['ide'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>Система управления студентами • Группа ИС-235.1 • <?= date('Y') ?></p>
            </div>
        </div>
    </div>
  <?php endif; ?>
</body>
</html>
