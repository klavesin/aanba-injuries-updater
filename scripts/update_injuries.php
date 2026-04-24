<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Moscow');

const ROOT_DIR = __DIR__ . '/..';
const DATA_DIR = ROOT_DIR . '/data';
const LOG_DIR = ROOT_DIR . '/logs';
const DATA_FILE = DATA_DIR . '/injuries.json';
const LOG_FILE = LOG_DIR . '/update_injuries.log';
const USER_AGENT = 'Mozilla/5.0 (compatible; AANBA-InjuriesBot/1.2; +https://aanba.info/)';

const STATUS_MAP_RU = [
    'Out' => 'Не сыграет',
    'Questionable' => 'Под вопросом',
    'Doubtful' => 'Скорее не сыграет',
    'Available' => 'Сыграет',
    'Probable' => 'Скорее сыграет',
    'Game-Time Decision' => 'Решение в день матча',
];

const TEAM_RU_MAP = [
    'Atlanta Hawks' => 'Атланта Хокс',
    'Boston Celtics' => 'Бостон Селтикс',
    'Brooklyn Nets' => 'Бруклин Нетс',
    'Charlotte Hornets' => 'Шарлотт Хорнетс',
    'Chicago Bulls' => 'Чикаго Буллз',
    'Cleveland Cavaliers' => 'Кливленд Кавальерс',
    'Dallas Mavericks' => 'Даллас Маверикс',
    'Denver Nuggets' => 'Денвер Наггетс',
    'Detroit Pistons' => 'Детройт Пистонс',
    'Golden State Warriors' => 'Голден Стэйт Уорриорз',
    'Houston Rockets' => 'Хьюстон Рокетс',
    'Indiana Pacers' => 'Индиана Пэйсерс',
    'Los Angeles Clippers' => 'Лос-Анджелес Клипперс',
    'Los Angeles Lakers' => 'Лос-Анджелес Лейкерс',
    'Memphis Grizzlies' => 'Мемфис Гриззлис',
    'Miami Heat' => 'Майами Хит',
    'Milwaukee Bucks' => 'Милуоки Бакс',
    'Minnesota Timberwolves' => 'Миннесота Тимбервулвз',
    'New Orleans Pelicans' => 'Нью-Орлеан Пеликанс',
    'New York Knicks' => 'Нью-Йорк Никс',
    'Oklahoma City Thunder' => 'Оклахома-Сити Тандер',
    'Orlando Magic' => 'Орландо Мэджик',
    'Philadelphia 76ers' => 'Филадельфия Сиксерс',
    'Phoenix Suns' => 'Финикс Санз',
    'Portland Trail Blazers' => 'Портленд Трэйл Блэйзерс',
    'Sacramento Kings' => 'Сакраменто Кингз',
    'San Antonio Spurs' => 'Сан-Антонио Спёрс',
    'Toronto Raptors' => 'Торонто Рэпторс',
    'Utah Jazz' => 'Юта Джаз',
    'Washington Wizards' => 'Вашингтон Уизардс',
];

const TEAM_ALIASES = [
    'Atlanta Hawks' => 'Atlanta Hawks', 'Atlanta' => 'Atlanta Hawks',
    'Boston Celtics' => 'Boston Celtics', 'Boston' => 'Boston Celtics',
    'Brooklyn Nets' => 'Brooklyn Nets', 'Brooklyn' => 'Brooklyn Nets',
    'Charlotte Hornets' => 'Charlotte Hornets', 'Charlotte' => 'Charlotte Hornets',
    'Chicago Bulls' => 'Chicago Bulls', 'Chicago' => 'Chicago Bulls',
    'Cleveland Cavaliers' => 'Cleveland Cavaliers', 'Cleveland' => 'Cleveland Cavaliers',
    'Dallas Mavericks' => 'Dallas Mavericks', 'Dallas' => 'Dallas Mavericks',
    'Denver Nuggets' => 'Denver Nuggets', 'Denver' => 'Denver Nuggets',
    'Detroit Pistons' => 'Detroit Pistons', 'Detroit' => 'Detroit Pistons',
    'Golden State Warriors' => 'Golden State Warriors', 'Golden State' => 'Golden State Warriors',
    'Houston Rockets' => 'Houston Rockets', 'Houston' => 'Houston Rockets',
    'Indiana Pacers' => 'Indiana Pacers', 'Indiana' => 'Indiana Pacers',
    'Los Angeles Clippers' => 'Los Angeles Clippers', 'LA Clippers' => 'Los Angeles Clippers', 'L.A. Clippers' => 'Los Angeles Clippers',
    'Los Angeles Lakers' => 'Los Angeles Lakers', 'L.A. Lakers' => 'Los Angeles Lakers',
    'Memphis Grizzlies' => 'Memphis Grizzlies', 'Memphis' => 'Memphis Grizzlies',
    'Miami Heat' => 'Miami Heat', 'Miami' => 'Miami Heat',
    'Milwaukee Bucks' => 'Milwaukee Bucks', 'Milwaukee' => 'Milwaukee Bucks',
    'Minnesota Timberwolves' => 'Minnesota Timberwolves', 'Minnesota' => 'Minnesota Timberwolves',
    'New Orleans Pelicans' => 'New Orleans Pelicans', 'New Orleans' => 'New Orleans Pelicans',
    'New York Knicks' => 'New York Knicks', 'New York' => 'New York Knicks',
    'Oklahoma City Thunder' => 'Oklahoma City Thunder', 'Oklahoma City' => 'Oklahoma City Thunder',
    'Orlando Magic' => 'Orlando Magic', 'Orlando' => 'Orlando Magic',
    'Philadelphia 76ers' => 'Philadelphia 76ers', 'Philadelphia' => 'Philadelphia 76ers',
    'Phoenix Suns' => 'Phoenix Suns', 'Phoenix' => 'Phoenix Suns',
    'Portland Trail Blazers' => 'Portland Trail Blazers', 'Portland' => 'Portland Trail Blazers',
    'Sacramento Kings' => 'Sacramento Kings', 'Sacramento' => 'Sacramento Kings',
    'San Antonio Spurs' => 'San Antonio Spurs', 'San Antonio' => 'San Antonio Spurs',
    'Toronto Raptors' => 'Toronto Raptors', 'Toronto' => 'Toronto Raptors',
    'Utah Jazz' => 'Utah Jazz', 'Utah' => 'Utah Jazz',
    'Washington Wizards' => 'Washington Wizards', 'Washington' => 'Washington Wizards',
];

const MONTH_MAP = [
    'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06',
    'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12',
];

bootstrap();

try {
    ensureDirectories();
    logLine('INFO', 'Запуск update_injuries.php');

    $officialRows = fetchOfficialNbaRows();
    if ($officialRows === []) {
        throw new RuntimeException('Official NBA report не дал ни одной записи. Последний успешный JSON оставлен без изменений.');
    }

    $espnRows = [];
    $cbsRows = [];

    try {
        $espnRows = fetchEspnRows();
        logLine('INFO', 'Получено строк из ESPN: ' . count($espnRows));
    } catch (Throwable $e) {
        logLine('WARN', 'ESPN недоступен: ' . $e->getMessage());
    }

    try {
        $cbsRows = fetchCbsRows();
        logLine('INFO', 'Получено строк из CBS: ' . count($cbsRows));
    } catch (Throwable $e) {
        logLine('WARN', 'CBS недоступен: ' . $e->getMessage());
    }

    $items = mergeRows($officialRows, $espnRows, $cbsRows);
    if ($items === []) {
        throw new RuntimeException('После merge не осталось ни одной записи. Последний успешный JSON оставлен без изменений.');
    }

    usort($items, static function (array $a, array $b): int {
        $orderA = statusOrder($a['raw_status'] ?? '');
        $orderB = statusOrder($b['raw_status'] ?? '');
        if ($orderA !== $orderB) {
            return $orderA <=> $orderB;
        }
        $teamCompare = strcmp((string)($a['team_ru'] ?? ''), (string)($b['team_ru'] ?? ''));
        if ($teamCompare !== 0) {
            return $teamCompare;
        }
        return strcmp((string)($a['player_ru'] ?? ''), (string)($b['player_ru'] ?? ''));
    });

    $payload = buildPayload($items, count($officialRows), count($espnRows), count($cbsRows), getOfficialPdfUrl());
    writeJsonAtomic(DATA_FILE, $payload);

    logLine('INFO', 'injuries.json обновлён. Записей: ' . count($items));
    respond(true, 'injuries.json успешно обновлён', [
        'last_updated' => $payload['last_updated'],
        'records' => count($items),
        'official_pdf_url' => getOfficialPdfUrl(),
    ]);
} catch (Throwable $e) {
    logLine('ERROR', $e->getMessage());
    respond(false, $e->getMessage(), [
        'last_successful_json_kept' => is_file(DATA_FILE),
    ], 500);
}

function bootstrap(): void
{
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
}

function ensureDirectories(): void
{
    foreach ([DATA_DIR, LOG_DIR] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Не удалось создать директорию: ' . $dir);
        }
    }
    if (!file_exists(LOG_FILE)) {
        @file_put_contents(LOG_FILE, '');
    }
}

function logLine(string $level, string $message): void
{
    $timestamp = formatMoscowDateTime();
    @file_put_contents(LOG_FILE, '[' . $timestamp . '] [' . $level . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function respond(bool $ok, string $message, array $extra = [], int $statusCode = 200): void
{
    if (PHP_SAPI !== 'cli') {
        http_response_code($statusCode);
    }

    echo json_encode(array_merge([
        'ok' => $ok,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    if (PHP_SAPI === 'cli') {
        exit($ok ? 0 : 1);
    }
}

function fetchOfficialNbaRows(): array
{
    $pdfUrl = discoverLatestOfficialPdfUrlDirect();
    if ($pdfUrl === '') {
        throw new RuntimeException('Не удалось найти доступный официальный injury report PDF NBA прямым перебором ссылок.');
    }
    setOfficialPdfUrl($pdfUrl);

    $pdfBinary = httpGetBinary($pdfUrl);
    $pdfText = extractPdfText($pdfBinary);
    if ($pdfText === '') {
        throw new RuntimeException('Не удалось извлечь текст из официального PDF-отчёта NBA.');
    }

    $rows = parseOfficialPdfText($pdfText);
    logLine('INFO', 'Получено строк из official NBA: ' . count($rows) . '. PDF: ' . $pdfUrl);
    return $rows;
}

function fetchEspnRows(): array
{
    $html = httpGet('https://www.espn.com/nba/injuries');
    $lines = normalizeHtmlToLines($html);
    $rows = [];
    $currentTeam = null;

    foreach ($lines as $line) {
        if (isset(TEAM_ALIASES[$line])) {
            $currentTeam = canonicalTeam($line);
            continue;
        }
        if ($currentTeam === null) {
            continue;
        }
        if ($line === 'NAME POS EST. RETURN DATE STATUS COMMENT' || $line === 'NAME POS RETURN DATE STATUS COMMENT') {
            continue;
        }
        $parsed = parseEspnLine($line, $currentTeam);
        if ($parsed !== null) {
            $rows[] = $parsed;
        }
    }

    return dedupeByKey($rows, static fn(array $row): string => buildSourceKey($row['player_name'], $row['team']));
}

function fetchCbsRows(): array
{
    $html = httpGet('https://www.cbssports.com/nba/injuries/');
    $lines = normalizeHtmlToLines($html);
    $rows = [];
    $currentTeam = null;

    foreach ($lines as $line) {
        if (isset(TEAM_ALIASES[$line])) {
            $currentTeam = canonicalTeam($line);
            continue;
        }
        if ($currentTeam === null) {
            continue;
        }
        if (str_contains($line, 'Player Position Updated Injury Injury Status')) {
            continue;
        }
        $parsed = parseCbsLine($line, $currentTeam);
        if ($parsed !== null) {
            $rows[] = $parsed;
        }
    }

    return dedupeByKey($rows, static fn(array $row): string => buildSourceKey($row['player_name'], $row['team']));
}

function discoverLatestOfficialPdfUrlDirect(): string
{
    $nowEt = new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
    $startEt = $nowEt->modify('+90 minutes');
    $startMinute = ((int)$startEt->format('i'));
    $roundedMinute = (int)(ceil($startMinute / 15) * 15);
    if ($roundedMinute === 60) {
        $startEt = $startEt->modify('+1 hour')->setTime((int)$startEt->format('H') + 1, 0);
    } else {
        $startEt = $startEt->setTime((int)$startEt->format('H'), $roundedMinute);
    }

    for ($i = 0; $i < 160; $i++) {
        $candidateEt = $startEt->modify('-' . ($i * 15) . ' minutes');
        $url = buildOfficialPdfUrlFromEt($candidateEt);
        if (urlExists($url)) {
            return $url;
        }
    }

    return '';
}

function buildOfficialPdfUrlFromEt(DateTimeImmutable $dtEt): string
{
    $date = $dtEt->format('Y-m-d');
    $hour12 = $dtEt->format('g');
    $minute = $dtEt->format('i');
    $ampm = strtoupper($dtEt->format('A'));
    return sprintf(
        'https://ak-static.cms.nba.com/referee/injury/Injury-Report_%s_%s_%s%s.pdf',
        $date,
        $hour12,
        $minute,
        $ampm
    );
}

function urlExists(string $url): bool
{
    try {
        $result = request($url, 'HEAD', [
            'connect_timeout' => 8,
            'timeout' => 12,
            'allow_status' => [200],
            'retries' => 2,
        ]);
        return ($result['code'] ?? 0) === 200;
    } catch (Throwable $e) {
        return false;
    }
}

function parseOfficialPdfTimestamp(string $url): int
{
    if (!preg_match('/Injury-Report_(\d{4}-\d{2}-\d{2})_(\d{1,2})_(\d{2})(AM|PM)\.pdf/i', $url, $m)) {
        return 0;
    }

    $hour = (int)$m[2];
    $minute = (int)$m[3];
    $period = strtoupper($m[4]);
    if ($period === 'PM' && $hour < 12) {
        $hour += 12;
    }
    if ($period === 'AM' && $hour === 12) {
        $hour = 0;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', sprintf('%s %02d:%02d', $m[1], $hour, $minute), new DateTimeZone('America/New_York'));
    return $dt instanceof DateTimeImmutable ? $dt->getTimestamp() : 0;
}

function parseOfficialPdfText(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), static fn(string $line): bool => $line !== ''));

    $rows = [];
    $currentGameDate = '';
    $currentGameTime = '';
    $currentMatchup = '';
    $currentTeam = '';
    $pending = null;

    foreach ($lines as $line) {
        if (str_starts_with($line, 'Injury Report:') || str_starts_with($line, 'Page ') || str_contains($line, 'Game Date Game Time Matchup Team Player Name Current Status Reason')) {
            continue;
        }

        $parts = preg_split('/\s{2,}/u', $line);
        if (!is_array($parts)) {
            continue;
        }
        $parts = array_values(array_filter(array_map('trim', $parts), static fn(string $part): bool => $part !== ''));
        if ($parts === []) {
            continue;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $parts[0] ?? '')) {
            $currentGameDate = array_shift($parts);
        }
        if (preg_match('/^\d{2}:\d{2}\s+\(ET\)$/', $parts[0] ?? '')) {
            $currentGameTime = array_shift($parts);
        }
        if (preg_match('/^[A-Z]{2,3}@[A-Z]{2,3}$/', $parts[0] ?? '')) {
            $currentMatchup = array_shift($parts);
        }
        if (isset(TEAM_ALIASES[$parts[0] ?? ''])) {
            $currentTeam = canonicalTeam((string)array_shift($parts));
        }
        if (($parts[0] ?? '') === 'NOT YET SUBMITTED') {
            continue;
        }
        if ($parts === []) {
            continue;
        }

        $statusIndex = findOfficialStatusIndex($parts);
        if ($statusIndex === null) {
            if ($pending !== null) {
                $pending['raw_reason'] = trim($pending['raw_reason'] . ' ' . implode(' ', $parts));
            }
            continue;
        }

        if ($pending !== null) {
            $rows[] = finalizeOfficialRow($pending);
            $pending = null;
        }

        $playerName = trim(implode(' ', array_slice($parts, 0, $statusIndex)));
        $status = trim($parts[$statusIndex]);
        $reason = trim(implode(' ', array_slice($parts, $statusIndex + 1)));

        if ($playerName === '' || $currentTeam === '') {
            continue;
        }

        $pending = [
            'player_name' => normalizePlayerName($playerName),
            'team' => $currentTeam,
            'raw_status' => $status,
            'raw_reason' => $reason,
            'report_date' => normalizeSlashDate($currentGameDate),
            'game_date' => normalizeSlashDate($currentGameDate),
            'game_time_et' => $currentGameTime,
            'matchup' => $currentMatchup,
        ];
    }

    if ($pending !== null) {
        $rows[] = finalizeOfficialRow($pending);
    }

    return dedupeByKey($rows, static fn(array $row): string => buildSourceKey($row['player_name'], $row['team']) . '|' . ($row['report_date'] ?? ''));
}

function parseEspnLine(string $line, string $team): ?array
{
    if (!preg_match('/^(?<name>.+?)(?<pos>PG|SG|SF|PF|C|G|F)\s+(?<rest>.+)$/u', $line, $m)) {
        return null;
    }

    $playerName = normalizePlayerName(trim($m['name']));
    $rest = trim($m['rest']);
    if ($playerName === '' || $rest === '') {
        return null;
    }

    $comment = '';
    if (preg_match('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2}:\s+.+$/', $rest, $commentMatch, PREG_OFFSET_CAPTURE)) {
        $comment = trim($commentMatch[0][0]);
        $prefix = trim(substr($rest, 0, (int)$commentMatch[0][1]));
    } else {
        $prefix = $rest;
    }

    $estimatedReturnRaw = '';
    if (preg_match('/^(Out for season|No timetable|TBD|Day-To-Day|(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2})\b/u', $prefix, $returnMatch)) {
        $estimatedReturnRaw = trim($returnMatch[1]);
        $prefix = trim(substr($prefix, strlen($estimatedReturnRaw)));
    }

    return [
        'source' => 'espn',
        'player_name' => $playerName,
        'team' => $team,
        'estimated_return_raw' => $estimatedReturnRaw,
        'comment_raw' => $comment,
        'status_hint_raw' => trim($prefix),
        'report_date' => todayYmd(),
    ];
}

function parseCbsLine(string $line, string $team): ?array
{
    $line = preg_replace('/([A-Za-z])([A-Z][a-z])/', '$1 $2', $line) ?? $line;

    if (!preg_match('/^(?<name>.+?)\s+(?<pos>PG|SG|SF|PF|C|G|F)\s+(?<updated>[A-Z][a-z]{2},\s+[A-Z][a-z]{2}\s+\d{1,2})\s+(?<injury>.+?)\s+(?<status>Expected to be out until at least\s+[A-Z][a-z]{2}\s+\d{1,2}|Out for season|No timetable available|Day-To-Day|Game-Time Decision|Questionable|Probable|Doubtful|Available)$/u', $line, $m)) {
        return null;
    }

    $playerName = extractFullNameFromMixedString($m['name']);
    if ($playerName === '') {
        return null;
    }

    $estimatedReturnRaw = '';
    if (preg_match('/Expected to be out until at least\s+([A-Z][a-z]{2}\s+\d{1,2})/u', $m['status'], $returnMatch)) {
        $estimatedReturnRaw = trim($returnMatch[1]);
    }

    return [
        'source' => 'cbs',
        'player_name' => normalizePlayerName($playerName),
        'team' => $team,
        'estimated_return_raw' => $estimatedReturnRaw,
        'comment_raw' => trim($m['status']),
        'status_hint_raw' => '',
        'report_date' => todayYmd(),
    ];
}

function mergeRows(array $officialRows, array $espnRows, array $cbsRows): array
{
    $espnIndex = [];
    foreach ($espnRows as $row) {
        $espnIndex[buildSourceKey($row['player_name'], $row['team'])] = $row;
    }

    $cbsIndex = [];
    foreach ($cbsRows as $row) {
        $cbsIndex[buildSourceKey($row['player_name'], $row['team'])] = $row;
    }

    $items = [];
    foreach ($officialRows as $official) {
        $key = buildSourceKey($official['player_name'], $official['team']);
        $secondary = $espnIndex[$key] ?? $cbsIndex[$key] ?? null;
        $returnSource = isset($espnIndex[$key]) ? 'espn' : (isset($cbsIndex[$key]) ? 'cbs' : 'none');

        $returnInfo = buildReturnInfo($secondary);
        $commentRu = translateCommentRu((string)($secondary['comment_raw'] ?? ''));

        $items[] = [
            'id' => buildItemId($official['player_name'], $official['team'], $official['report_date']),
            'date' => $official['report_date'],
            'game_date' => $official['game_date'],
            'game_time_et' => $official['game_time_et'],
            'matchup' => $official['matchup'],
            'player' => $official['player_name'],
            'player_ru' => $official['player_name'],
            'team' => $official['team'],
            'team_ru' => teamRu($official['team']),
            'raw_status' => $official['raw_status'],
            'raw_reason' => $official['raw_reason'],
            'raw_comment' => (string)($secondary['comment_raw'] ?? ''),
            'status_ru' => translateStatusRu($official['raw_status']),
            'reason_ru' => translateReasonRu($official['raw_reason']),
            'comment_ru' => $commentRu !== '' ? $commentRu : 'Без дополнительного комментария',
            'estimated_return_date_raw' => $returnInfo['estimated_return_date_raw'],
            'estimated_return_date_ru' => $returnInfo['estimated_return_date_ru'],
            'reevaluation_raw' => $returnInfo['reevaluation_raw'],
            'reevaluation_ru' => $returnInfo['reevaluation_ru'],
            'return_or_reevaluation_ru' => $returnInfo['return_or_reevaluation_ru'],
            'source_status' => 'official_nba',
            'source_return' => $returnSource,
            'updated_at' => formatMoscowDateTime(),
            'display' => [
                'player' => $official['player_name'],
                'team' => teamRu($official['team']),
                'status' => translateStatusRu($official['raw_status']),
                'reason' => translateReasonRu($official['raw_reason']),
                'return_or_reevaluation' => $returnInfo['return_or_reevaluation_ru'],
                'comment' => $commentRu !== '' ? $commentRu : 'Без дополнительного комментария',
            ],
        ];
    }

    return $items;
}

function buildReturnInfo(?array $secondary): array
{
    $estimatedReturnRaw = trim((string)($secondary['estimated_return_raw'] ?? ''));
    $commentRaw = trim((string)($secondary['comment_raw'] ?? ''));
    $estimatedReturnRu = extractReturnDateRu($estimatedReturnRaw, $commentRaw);
    $reevaluationRu = extractReevaluationRu($commentRaw);

    return [
        'estimated_return_date_raw' => $estimatedReturnRaw,
        'estimated_return_date_ru' => $estimatedReturnRu !== '' ? $estimatedReturnRu : 'Уточняется',
        'reevaluation_raw' => $reevaluationRu !== '' ? $commentRaw : '',
        'reevaluation_ru' => $reevaluationRu,
        'return_or_reevaluation_ru' => $reevaluationRu !== '' ? $reevaluationRu : ($estimatedReturnRu !== '' ? $estimatedReturnRu : 'Уточняется'),
    ];
}

function buildPayload(array $items, int $officialCount, int $espnCount, int $cbsCount, ?string $officialPdfUrl): array
{
    $updatedAt = formatMoscowDateTime();
    return [
        'site_title' => 'Травмы игроков NBA',
        'last_updated' => $updatedAt,
        'status' => 'ok',
        'source_label' => 'NBA + ESPN',
        'data_state_label' => 'Актуальные данные',
        'meta' => [
            'timezone' => 'Europe/Moscow',
            'generated_at' => $updatedAt,
            'schema_version' => '1.0.0',
            'primary_status_source' => 'official_nba',
            'return_source_priority' => ['espn', 'cbs'],
            'status_map' => STATUS_MAP_RU,
            'notes' => [
                'Официальный NBA injury report — основной источник правды для статуса и причины.',
                'ESPN используется для даты возврата, повторного осмотра и комментария.',
                'CBS используется как fallback, если у ESPN нет return info.',
                'При ошибке обновления последний успешный injuries.json не перезаписывается.',
            ],
            'stats' => [
                'official_pdf_url' => $officialPdfUrl,
                'official_rows' => $officialCount,
                'espn_rows' => $espnCount,
                'cbs_rows' => $cbsCount,
            ],
        ],
        'items' => $items,
    ];
}

function writeJsonAtomic(string $path, array $payload): void
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Не удалось сериализовать итоговый JSON.');
    }
    $tmpPath = $path . '.tmp';
    if (file_put_contents($tmpPath, $json) === false) {
        throw new RuntimeException('Не удалось записать временный JSON-файл.');
    }
    if (!rename($tmpPath, $path)) {
        @unlink($tmpPath);
        throw new RuntimeException('Не удалось заменить итоговый JSON-файл.');
    }
}

function httpGet(string $url): string { return request($url)['body']; }
function httpGetBinary(string $url): string { return request($url)['body']; }

function request(string $url, string $method = 'GET', array $options = []): array
{
    $connectTimeout = (int)($options['connect_timeout'] ?? 10);
    $timeout = (int)($options['timeout'] ?? 35);
    $allowStatus = $options['allow_status'] ?? [];
    $retries = (int)($options['retries'] ?? 1);

    $attempt = 0;
    $lastError = '';
    $lastCode = 0;

    while ($attempt < $retries) {
        $attempt++;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $headers = [
                'Accept: text/html,application/pdf,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,ru;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_USERAGENT => USER_AGENT,
                CURLOPT_ENCODING => '',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_NOBODY => $method === 'HEAD',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ]);

            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $lastCode = $code;
            $lastError = $error;

            if ($body !== false && ($code < 400 || in_array($code, $allowStatus, true))) {
                return ['body' => (string)$body, 'code' => $code];
            }
        } else {
            $headerLines = [
                'User-Agent: ' . USER_AGENT,
                'Accept: */*',
                'Accept-Language: en-US,en;q=0.9,ru;q=0.8',
            ];
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("
", $headerLines) . "
",
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $body = @file_get_contents($url, false, $context);
            if ($body !== false) {
                return ['body' => $body, 'code' => 200];
            }
            $lastError = 'stream request failed';
        }

        if ($attempt < $retries) {
            usleep(300000 * $attempt);
        }
    }

    throw new RuntimeException(sprintf('Ошибка запроса к %s. HTTP %d. %s', $url, $lastCode, $lastError));
}

function extractPdfText(string $binary): string
{
    $text = extractPdfTextViaPdftotext($binary);
    if ($text !== '') {
        return normalizePdfText($text);
    }
    return normalizePdfText(extractPdfTextPurePhp($binary));
}

function extractPdfTextViaPdftotext(string $binary): string
{
    if (!function_exists('shell_exec')) {
        return '';
    }
    $command = trim((string)@shell_exec('command -v pdftotext 2>/dev/null'));
    if ($command === '') {
        return '';
    }
    $tmp = tempnam(sys_get_temp_dir(), 'injury_pdf_');
    if ($tmp === false) {
        return '';
    }
    file_put_contents($tmp, $binary);
    $result = (string)@shell_exec(escapeshellarg($command) . ' -layout -nopgbrk ' . escapeshellarg($tmp) . ' - 2>/dev/null');
    @unlink($tmp);
    return $result;
}

function extractPdfTextPurePhp(string $binary): string
{
    if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $binary, $matches)) {
        return '';
    }
    $text = '';
    foreach ($matches[1] as $stream) {
        $decoded = tryInflate($stream);
        if ($decoded === '') {
            $decoded = $stream;
        }
        if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $decoded, $strings)) {
            foreach ($strings[0] as $value) {
                $text .= unescapePdfString(substr($value, 1, -1)) . "\n";
            }
        }
    }
    return $text;
}

function tryInflate(string $stream): string
{
    $stream = ltrim($stream, "\r\n");
    foreach ([
        static fn(string $data): string|false => @gzuncompress($data),
        static fn(string $data): string|false => @gzinflate($data),
        static fn(string $data): string|false => @gzdecode($data),
    ] as $inflate) {
        $decoded = $inflate($stream);
        if (is_string($decoded) && $decoded !== '') {
            return $decoded;
        }
    }
    return '';
}

function unescapePdfString(string $value): string
{
    $value = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $value);
    $value = preg_replace_callback('/\\\\([0-7]{1,3})/', static function (array $m): string {
        return chr(octdec($m[1]));
    }, $value) ?? $value;
    return trim($value);
}

function normalizePdfText(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[\t\x0B\x0C]+/u', ' ', $text) ?? $text;
    return trim($text);
}

function normalizeHtmlToLines(string $html): array
{
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
    $html = str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>', '</tr>', '</h1>', '</h2>', '</h3>', '</h4>'], "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    $rawLines = explode("\n", $text);
    $lines = [];
    foreach ($rawLines as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line) ?? $line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    return $lines;
}

function finalizeOfficialRow(array $row): array
{
    $row['player_name'] = normalizePlayerName((string)$row['player_name']);
    $row['team'] = canonicalTeam((string)$row['team']);
    $row['raw_reason'] = trim(preg_replace('/\s+/u', ' ', (string)$row['raw_reason']) ?? (string)$row['raw_reason']);
    if ($row['raw_reason'] === '') {
        $row['raw_reason'] = '-';
    }
    return $row;
}

function findOfficialStatusIndex(array $parts): ?int
{
    foreach ($parts as $index => $part) {
        if (array_key_exists($part, STATUS_MAP_RU)) {
            return $index;
        }
    }
    return null;
}

function normalizePlayerName(string $name): string
{
    $name = trim($name);
    if (str_contains($name, ',')) {
        [$last, $first] = array_map('trim', explode(',', $name, 2));
        $name = trim($first . ' ' . $last);
    }
    $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
    return trim($name);
}

function canonicalTeam(string $team): string { return TEAM_ALIASES[$team] ?? $team; }
function teamRu(string $team): string { return TEAM_RU_MAP[canonicalTeam($team)] ?? $team; }
function translateStatusRu(string $status): string { return STATUS_MAP_RU[$status] ?? $status; }

function translateReasonRu(string $reason): string
{
    $reason = trim($reason);
    if ($reason === '' || $reason === '-') {
        return 'Причина уточняется';
    }

    $map = [
        'Injury/Illness - ' => '',
        'Illness - ' => 'Болезнь — ',
        'Not With Team - ' => 'Вне расположения команды — ',
        'G League - Two-Way' => 'G League — двусторонний контракт',
        'G League - On Assignment' => 'G League — командирован',
        'Return To Competition Reconditioning' => 'Набор кондиций после возвращения',
        'Injury Management' => 'контроль нагрузки',
        'Maintenance' => 'контроль нагрузки',
        'Soreness' => 'болезненность',
        'Surgery' => 'после операции',
        'Contusion' => 'ушиб',
        'Strain' => 'растяжение',
        'Sprain' => 'растяжение',
        'Hamstring' => 'подколенное сухожилие',
        'Calf' => 'икра',
        'Knee' => 'колено',
        'Ankle' => 'голеностоп',
        'High Ankle' => 'верхний голеностоп',
        'Foot' => 'стопа',
        'Toe' => 'палец ноги',
        'Back' => 'спина',
        'Hip' => 'бедро',
        'Groin' => 'пах',
        'Oblique' => 'косая мышца корпуса',
        'Achilles' => 'ахилл',
        'Wrist' => 'запястье',
        'Finger' => 'палец',
        'Thumb' => 'большой палец',
        'Hand' => 'кисть',
        'Shoulder' => 'плечо',
        'Elbow' => 'локоть',
        'Neck' => 'шея',
        'Ribs' => 'рёбра',
        'Personal' => 'личные причины',
        'Rest' => 'отдых',
        'Right' => 'Правое',
        'Left' => 'Левое',
    ];

    uksort($map, static fn(string $a, string $b): int => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));
    foreach ($map as $en => $ru) {
        $reason = str_ireplace($en, $ru, $reason);
    }

    $reason = preg_replace('/\s*;\s*/u', '; ', $reason) ?? $reason;
    $reason = preg_replace('/\s{2,}/u', ' ', $reason) ?? $reason;
    $reason = trim($reason, " -—;");
    return upperFirst($reason);
}

function translateCommentRu(string $comment): string
{
    $comment = trim($comment);
    if ($comment === '') {
        return '';
    }

    $patterns = [
        '/Will be re-evaluated in one week/i' => 'Повторный осмотр через 1 неделю',
        '/Will be re-evaluated in two weeks/i' => 'Повторный осмотр через 2 недели',
        '/expected to miss at least one week/i' => 'Ожидается пропуск как минимум одной недели',
        '/expected to miss at least two weeks/i' => 'Ожидается пропуск как минимум двух недель',
        '/is questionable for/i' => 'Участие под вопросом',
        '/questionable for/i' => 'Участие под вопросом',
        '/doubtful for/i' => 'Скорее не сыграет',
        '/probable for/i' => 'Скорее сыграет',
        '/expected to play/i' => 'Ожидается участие',
        '/available for/i' => 'Ожидается участие',
        '/has been ruled out/i' => 'Не сыграет',
    ];

    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $comment)) {
            return $replacement;
        }
    }

    if (preg_match('/Expected to be out until at least\s+([A-Z][a-z]{2}\s+\d{1,2})/i', $comment, $m)) {
        $dateRu = monthDayToRu($m[1]);
        return $dateRu !== '' ? 'Ожидается отсутствие как минимум до ' . $dateRu : 'Срок возвращения уточняется';
    }

    return 'Дополнительные детали доступны в raw_comment';
}

function extractReturnDateRu(string $estimatedReturnRaw, string $commentRaw): string
{
    if ($estimatedReturnRaw !== '') {
        $dateRu = monthDayToRu($estimatedReturnRaw);
        if ($dateRu !== '') {
            return $dateRu;
        }
    }
    if (preg_match('/Expected to be out until at least\s+([A-Z][a-z]{2}\s+\d{1,2})/i', $commentRaw, $m)) {
        $dateRu = monthDayToRu($m[1]);
        if ($dateRu !== '') {
            return $dateRu;
        }
    }
    return '';
}

function extractReevaluationRu(string $commentRaw): string
{
    if (preg_match('/re-evaluated in one week/i', $commentRaw)) {
        return 'Повторный осмотр через 1 неделю';
    }
    if (preg_match('/re-evaluated in two weeks/i', $commentRaw)) {
        return 'Повторный осмотр через 2 недели';
    }
    return '';
}

function monthDayToRu(string $monthDay): string
{
    if (!preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2})$/', trim($monthDay), $m)) {
        return '';
    }
    $month = MONTH_MAP[$m[1]] ?? null;
    if ($month === null) {
        return '';
    }

    $day = str_pad($m[2], 2, '0', STR_PAD_LEFT);
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
    $year = (int)$now->format('Y');
    $currentMonth = (int)$now->format('n');
    $targetMonth = (int)$month;
    if ($targetMonth < ($currentMonth - 6)) {
        $year += 1;
    }

    return sprintf('%s.%s.%04d', $day, $month, $year);
}

function extractFullNameFromMixedString(string $value): string
{
    $value = trim($value);
    if (preg_match('/([A-Z][A-Za-z\'\.-]+(?:\s+[A-Z][A-Za-z\'\.-]+){1,3})$/u', $value, $m)) {
        return trim($m[1]);
    }
    return '';
}

function buildSourceKey(string $playerName, string $team): string
{
    return slugify(normalizePlayerName($playerName)) . '|' . slugify(canonicalTeam($team));
}

function buildItemId(string $playerName, string $team, string $date): string
{
    return slugify($playerName) . '-' . slugify($team) . '-' . str_replace('-', '', $date);
}

function dedupeByKey(array $rows, callable $callback): array
{
    $result = [];
    foreach ($rows as $row) {
        $result[$callback($row)] = $row;
    }
    return array_values($result);
}

function slugify(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = transliterate($value);
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? $value;
    return trim($value, '-');
}

function transliterate(string $value): string
{
    return strtr($value, [
        'а' => 'a','б' => 'b','в' => 'v','г' => 'g','д' => 'd','е' => 'e','ё' => 'e','ж' => 'zh','з' => 'z','и' => 'i','й' => 'y',
        'к' => 'k','л' => 'l','м' => 'm','н' => 'n','о' => 'o','п' => 'p','р' => 'r','с' => 's','т' => 't','у' => 'u','ф' => 'f',
        'х' => 'h','ц' => 'ts','ч' => 'ch','ш' => 'sh','щ' => 'sch','ъ' => '','ы' => 'y','ь' => '','э' => 'e','ю' => 'yu','я' => 'ya',
    ]);
}

function upperFirst(string $value): string
{
    if ($value === '') {
        return $value;
    }
    return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
}

function todayYmd(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('Y-m-d');
}

function normalizeSlashDate(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return todayYmd();
    }
    $dt = DateTimeImmutable::createFromFormat('m/d/Y', $value, new DateTimeZone('America/New_York'));
    return $dt instanceof DateTimeImmutable ? $dt->format('Y-m-d') : todayYmd();
}

function formatMoscowDateTime(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i') . ' МСК';
}

function statusOrder(string $rawStatus): int
{
    return match ($rawStatus) {
        'Out' => 1,
        'Doubtful' => 2,
        'Questionable' => 3,
        'Game-Time Decision' => 4,
        'Probable' => 5,
        'Available' => 6,
        default => 99,
    };
}

function absolutizeUrl(string $url, string $baseUrl): string
{
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    $base = parse_url($baseUrl);
    $scheme = $base['scheme'] ?? 'https';
    $host = $base['host'] ?? '';
    if (str_starts_with($url, '/')) {
        return $scheme . '://' . $host . $url;
    }
    $path = $base['path'] ?? '/';
    $directory = rtrim(dirname($path), '/\\');
    return $scheme . '://' . $host . $directory . '/' . ltrim($url, '/');
}

function getOfficialPdfUrl(): ?string { return $GLOBALS['official_pdf_url'] ?? null; }
function setOfficialPdfUrl(string $url): void { $GLOBALS['official_pdf_url'] = $url; }
