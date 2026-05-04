<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Moscow');

const ROOT_DIR = __DIR__ . '/..';
const DATA_DIR = ROOT_DIR . '/data';
const LOG_DIR = ROOT_DIR . '/logs';
const DATA_FILE = DATA_DIR . '/injuries.json';
const LOG_FILE = LOG_DIR . '/update_injuries.log';

const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
const OFFICIAL_REPORT_PAGE = 'https://official.nba.com/nba-injury-report-2025-26-season/';

const PDF_CONNECT_TIMEOUT = 30;
const PDF_DOWNLOAD_TIMEOUT = 120;
const PDF_CANDIDATES_TO_TRY = 5;

const STATUS_MAP_RU = [
    'Out' => 'Не сыграет',
    'Questionable' => 'Под вопросом',
    'Doubtful' => 'Скорее не сыграет',
    'Available' => 'Доступен к игре',
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
    'Atlanta Hawks' => 'Atlanta Hawks', 'Atlanta' => 'Atlanta Hawks', 'ATL' => 'Atlanta Hawks',
    'Boston Celtics' => 'Boston Celtics', 'Boston' => 'Boston Celtics', 'BOS' => 'Boston Celtics',
    'Brooklyn Nets' => 'Brooklyn Nets', 'Brooklyn' => 'Brooklyn Nets', 'BKN' => 'Brooklyn Nets',
    'Charlotte Hornets' => 'Charlotte Hornets', 'Charlotte' => 'Charlotte Hornets', 'CHA' => 'Charlotte Hornets',
    'Chicago Bulls' => 'Chicago Bulls', 'Chicago' => 'Chicago Bulls', 'CHI' => 'Chicago Bulls',
    'Cleveland Cavaliers' => 'Cleveland Cavaliers', 'Cleveland' => 'Cleveland Cavaliers', 'CLE' => 'Cleveland Cavaliers',
    'Dallas Mavericks' => 'Dallas Mavericks', 'Dallas' => 'Dallas Mavericks', 'DAL' => 'Dallas Mavericks',
    'Denver Nuggets' => 'Denver Nuggets', 'Denver' => 'Denver Nuggets', 'DEN' => 'Denver Nuggets',
    'Detroit Pistons' => 'Detroit Pistons', 'Detroit' => 'Detroit Pistons', 'DET' => 'Detroit Pistons',
    'Golden State Warriors' => 'Golden State Warriors', 'Golden State' => 'Golden State Warriors', 'GSW' => 'Golden State Warriors',
    'Houston Rockets' => 'Houston Rockets', 'Houston' => 'Houston Rockets', 'HOU' => 'Houston Rockets',
    'Indiana Pacers' => 'Indiana Pacers', 'Indiana' => 'Indiana Pacers', 'IND' => 'Indiana Pacers',
    'Los Angeles Clippers' => 'Los Angeles Clippers', 'LA Clippers' => 'Los Angeles Clippers', 'L.A. Clippers' => 'Los Angeles Clippers', 'LAC' => 'Los Angeles Clippers',
    'Los Angeles Lakers' => 'Los Angeles Lakers', 'L.A. Lakers' => 'Los Angeles Lakers', 'LAL' => 'Los Angeles Lakers',
    'Memphis Grizzlies' => 'Memphis Grizzlies', 'Memphis' => 'Memphis Grizzlies', 'MEM' => 'Memphis Grizzlies',
    'Miami Heat' => 'Miami Heat', 'Miami' => 'Miami Heat', 'MIA' => 'Miami Heat',
    'Milwaukee Bucks' => 'Milwaukee Bucks', 'Milwaukee' => 'Milwaukee Bucks', 'MIL' => 'Milwaukee Bucks',
    'Minnesota Timberwolves' => 'Minnesota Timberwolves', 'Minnesota' => 'Minnesota Timberwolves', 'MIN' => 'Minnesota Timberwolves',
    'New Orleans Pelicans' => 'New Orleans Pelicans', 'New Orleans' => 'New Orleans Pelicans', 'NOP' => 'New Orleans Pelicans',
    'New York Knicks' => 'New York Knicks', 'New York' => 'New York Knicks', 'NYK' => 'New York Knicks',
    'Oklahoma City Thunder' => 'Oklahoma City Thunder', 'Oklahoma City' => 'Oklahoma City Thunder', 'OKC' => 'Oklahoma City Thunder',
    'Orlando Magic' => 'Orlando Magic', 'Orlando' => 'Orlando Magic', 'ORL' => 'Orlando Magic',
    'Philadelphia 76ers' => 'Philadelphia 76ers', 'Philadelphia' => 'Philadelphia 76ers', 'PHI' => 'Philadelphia 76ers',
    'Phoenix Suns' => 'Phoenix Suns', 'Phoenix' => 'Phoenix Suns', 'PHX' => 'Phoenix Suns',
    'Portland Trail Blazers' => 'Portland Trail Blazers', 'Portland' => 'Portland Trail Blazers', 'POR' => 'Portland Trail Blazers',
    'Sacramento Kings' => 'Sacramento Kings', 'Sacramento' => 'Sacramento Kings', 'SAC' => 'Sacramento Kings',
    'San Antonio Spurs' => 'San Antonio Spurs', 'San Antonio' => 'San Antonio Spurs', 'SAS' => 'San Antonio Spurs',
    'Toronto Raptors' => 'Toronto Raptors', 'Toronto' => 'Toronto Raptors', 'TOR' => 'Toronto Raptors',
    'Utah Jazz' => 'Utah Jazz', 'Utah' => 'Utah Jazz', 'UTA' => 'Utah Jazz',
    'Washington Wizards' => 'Washington Wizards', 'Washington' => 'Washington Wizards', 'WAS' => 'Washington Wizards',
];

const MONTH_MAP = [
    'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06',
    'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12',
];

/**
 * Ручной словарь — главный источник качественного написания имён.
 * Если игрока тут нет, включается fallback-транслитерация, чтобы в интерфейсе не было английских имён.
 */
const PLAYER_RU_MAP = [
    'LeBron James' => 'Леброн Джеймс',
    'Anthony Davis' => 'Энтони Дэвис',
    'Luka Doncic' => 'Лука Дончич',
    'Austin Reaves' => 'Остин Ривз',
    'Rui Hachimura' => 'Руи Хатимура',
    'Dorian Finney-Smith' => 'Дориан Финни-Смит',
    'Gabe Vincent' => 'Гэйб Винсент',
    'Jarred Vanderbilt' => 'Джарред Вандербилт',
    'Jaxson Hayes' => 'Джексон Хэйс',
    'Maxi Kleber' => 'Макси Клебер',
    'Christian Wood' => 'Кристиан Вуд',
    'Nikola Jokic' => 'Никола Йокич',
    'Jamal Murray' => 'Джамал Мюррей',
    'Michael Porter Jr.' => 'Майкл Портер мл.',
    'Aaron Gordon' => 'Аарон Гордон',
    'Russell Westbrook' => 'Расселл Уэстбрук',
    'Stephen Curry' => 'Стефен Карри',
    'Draymond Green' => 'Дрэймонд Грин',
    'Jimmy Butler' => 'Джимми Батлер',
    'Klay Thompson' => 'Клэй Томпсон',
    'Jonathan Kuminga' => 'Джонатан Куминга',
    'Brandin Podziemski' => 'Брэндин Подземски',
    'Andrew Wiggins' => 'Эндрю Уиггинс',
    'Giannis Antetokounmpo' => 'Яннис Адетокунбо',
    'Damian Lillard' => 'Дэмиан Лиллард',
    'Khris Middleton' => 'Крис Миддлтон',
    'Brook Lopez' => 'Брук Лопес',
    'Bobby Portis' => 'Бобби Портис',
    'Joel Embiid' => 'Джоэл Эмбиид',
    'Tyrese Maxey' => 'Тайриз Макси',
    'Ayo Dosunmu' => 'Айо Досунму',
    'Jeremy Sochan' => 'Джереми Сохан',
    'Donte DiVincenzo' => 'Донте Дивинченцо',
    'Carter Bryant' => 'Картер Брайант',
    'Paul George' => 'Пол Джордж',
    'Kyle Lowry' => 'Кайл Лаури',
    'Kelly Oubre Jr.' => 'Келли Убре мл.',
    'Jayson Tatum' => 'Джейсон Тейтум',
    'Jaylen Brown' => 'Джейлен Браун',
    'Kristaps Porzingis' => 'Кристапс Порзингис',
    'Jrue Holiday' => 'Дрю Холидэй',
    'Derrick White' => 'Деррик Уайт',
    'Al Horford' => 'Эл Хорфорд',
    'Shai Gilgeous-Alexander' => 'Шэй Гилджес-Александер',
    'Jalen Williams' => 'Джейлен Уильямс',
    'Chet Holmgren' => 'Чет Холмгрен',
    'Isaiah Hartenstein' => 'Айзейя Хартенштайн',
    'Luguentz Dort' => 'Лугенц Дорт',
    'Cason Wallace' => 'Кейсон Уоллес',
    'Anthony Edwards' => 'Энтони Эдвардс',
    'Karl-Anthony Towns' => 'Карл-Энтони Таунс',
    'Rudy Gobert' => 'Руди Гобер',
    'Julius Randle' => 'Джулиус Рэндл',
    'Mike Conley' => 'Майк Конли',
    'Naz Reid' => 'Наз Рид',
    'Ja Morant' => 'Джа Морант',
    'Jaren Jackson Jr.' => 'Джарен Джексон мл.',
    'Desmond Bane' => 'Десмонд Бэйн',
    'Zach Edey' => 'Зак Иди',
    'Marcus Smart' => 'Маркус Смарт',
    'Kevin Durant' => 'Кевин Дюрант',
    'Devin Booker' => 'Девин Букер',
    'Bradley Beal' => 'Брэдли Бил',
    'Tyus Jones' => 'Тайус Джонс',
    'Jusuf Nurkic' => 'Юсуф Нуркич',
    'Kawhi Leonard' => 'Кавай Леонард',
    'James Harden' => 'Джеймс Харден',
    'Norman Powell' => 'Норман Пауэлл',
    'Ivica Zubac' => 'Ивица Зубац',
    'Fred VanVleet' => 'Фред Ванвлит',
    'Alperen Sengun' => 'Алперен Шенгюн',
    'Jalen Green' => 'Джейлен Грин',
    'Jabari Smith Jr.' => 'Джабари Смит мл.',
    'Amen Thompson' => 'Амен Томпсон',
    'Dillon Brooks' => 'Диллон Брукс',
    'Steven Adams' => 'Стивен Адамс',
    'Donovan Mitchell' => 'Донован Митчелл',
    'Darius Garland' => 'Дариус Гарленд',
    'Evan Mobley' => 'Эван Мобли',
    'Jarrett Allen' => 'Джарретт Аллен',
    'Max Strus' => 'Макс Струс',
    'Thomas Bryant' => 'Томас Брайант',
    'Jalen Brunson' => 'Джейлен Брансон',
    'Josh Hart' => 'Джош Харт',
    'Mikal Bridges' => 'Микал Бриджес',
    'OG Anunoby' => 'Оу Джи Ануноби',
    'Mitchell Robinson' => 'Митчелл Робинсон',
    'Immanuel Quickley' => 'Иммануэль Куикли',
    'Scottie Barnes' => 'Скотти Барнс',
    'RJ Barrett' => 'Ар Джей Барретт',
    'Jakob Poeltl' => 'Якоб Пёлтль',
    'Trae Young' => 'Трей Янг',
    'Dyson Daniels' => 'Дайсон Дэниэлс',
    'Dejounte Murray' => 'Деджонте Мюррей',
    'Zaccharie Risacher' => 'Заккари Рисаше',
    'RayJ Dennis' => 'Рэй Джей Деннис',
    'Victor Wembanyama' => 'Виктор Вембаньяма',
    'Devin Vassell' => 'Девин Васселл',
    'Keldon Johnson' => 'Келдон Джонсон',
    'De\'Aaron Fox' => 'Деарон Фокс',
    'Domantas Sabonis' => 'Домантас Сабонис',
    'DeMar DeRozan' => 'Демар Дерозан',
    'Zach LaVine' => 'Зак Лавин',
    'Keegan Murray' => 'Киган Мюррей',
    'Cade Cunningham' => 'Кейд Каннингем',
    'Ausar Thompson' => 'Озар Томпсон',
    'Jalen Duren' => 'Джейлен Дюрен',
    'Paolo Banchero' => 'Паоло Банкеро',
    'Franz Wagner' => 'Франц Вагнер',
    'Jalen Suggs' => 'Джейлен Саггс',
    'LaMelo Ball' => 'Ламело Болл',
    'Brandon Miller' => 'Брэндон Миллер',
    'Miles Bridges' => 'Майлз Бриджес',
    'Zion Williamson' => 'Зайон Уильямсон',
    'CJ McCollum' => 'Си Джей Макколлум',
    'Brandon Ingram' => 'Брэндон Ингрэм',
    'Herbert Jones' => 'Херберт Джонс',
    'Trey Murphy III' => 'Трей Мёрфи III',
    'Bam Adebayo' => 'Бэм Адебайо',
    'Tyler Herro' => 'Тайлер Хирро',
    'Terry Rozier' => 'Терри Розир',
    'LaMelo Ball' => 'Ламело Болл',
    'Anfernee Simons' => 'Энферни Саймонс',
    'Scoot Henderson' => 'Скут Хендерсон',
    'Shaedon Sharpe' => 'Шейдон Шарп',
    'Lauri Markkanen' => 'Лаури Маркканен',
    'Walker Kessler' => 'Уокер Кесслер',
    'Jordan Clarkson' => 'Джордан Кларксон',
    'Jordan Poole' => 'Джордан Пул',
    'Kyle Kuzma' => 'Кайл Кузма',
    'Alex Sarr' => 'Алекс Сарр',
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
        logLine('WARN', 'ESPN недоступен или не распарсился: ' . $e->getMessage());
    }

    try {
        $cbsRows = fetchCbsRows();
        logLine('INFO', 'Получено строк из CBS: ' . count($cbsRows));
    } catch (Throwable $e) {
        logLine('WARN', 'CBS недоступен или не распарсился: ' . $e->getMessage());
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
    $line = '[' . $timestamp . '] [' . $level . '] ' . $message . PHP_EOL;

    @file_put_contents(LOG_FILE, $line, FILE_APPEND);

    if (PHP_SAPI === 'cli') {
        echo $line;
        if (function_exists('flush')) {
            @flush();
        }
    }
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
        echo PHP_EOL;
        exit($ok ? 0 : 1);
    }
}

function fetchOfficialNbaRows(): array
{
    $pdfUrls = discoverOfficialPdfUrlsDirect();
    if ($pdfUrls === []) {
        throw new RuntimeException('Не удалось найти свежие официальные injury report PDF NBA на странице official.nba.com.');
    }

    $lastError = '';

    foreach (array_slice($pdfUrls, 0, PDF_CANDIDATES_TO_TRY) as $index => $pdfUrl) {
        try {
            $humanIndex = $index + 1;
            logLine('INFO', 'Пробую официальный PDF NBA #' . $humanIndex . ': ' . $pdfUrl);

            setOfficialPdfUrl($pdfUrl);

            $pdfBinary = downloadOfficialPdfBinary($pdfUrl);
            logLine('INFO', 'PDF скачан. Размер: ' . strlen($pdfBinary) . ' байт. Извлекаю текст.');

            $pdfText = extractPdfText($pdfBinary);
            if ($pdfText === '') {
                throw new RuntimeException('Не удалось извлечь текст из PDF.');
            }

            $rows = parseOfficialPdfText($pdfText);
            if ($rows === []) {
                throw new RuntimeException('PDF распознан, но parser не получил ни одной строки.');
            }

            logLine('INFO', 'Получено строк из official NBA: ' . count($rows) . '. PDF: ' . $pdfUrl);
            return $rows;
        } catch (Throwable $e) {
            $lastError = $e->getMessage();
            logLine('WARN', 'Не удалось обработать PDF ' . $pdfUrl . ': ' . $lastError);
        }
    }

    throw new RuntimeException('Не удалось скачать/распарсить ни один официальный PDF NBA. Последняя ошибка: ' . $lastError);
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
    $urls = discoverOfficialPdfUrlsDirect();
    return $urls[0] ?? '';
}

function discoverOfficialPdfUrlsDirect(): array
{
    logLine('INFO', 'Ищу PDF NBA injury report на официальной странице: ' . OFFICIAL_REPORT_PAGE);

    try {
        $html = httpGet(OFFICIAL_REPORT_PAGE);
    } catch (Throwable $e) {
        logLine('WARN', 'Не удалось открыть страницу NBA injury reports: ' . $e->getMessage());
        return [];
    }

    $urls = [];

    if (preg_match_all('/href=["\']([^"\']*Injury-Report_\d{4}-\d{2}-\d{2}_\d{1,2}_\d{2}(?:AM|PM)\.pdf[^"\']*)["\']/i', $html, $matches)) {
        foreach ($matches[1] as $href) {
            $url = html_entity_decode((string)$href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $url = absolutizeUrl($url, OFFICIAL_REPORT_PAGE);
            $url = preg_replace('/\.pdf.*$/i', '.pdf', $url) ?? $url;

            if (parseOfficialPdfTimestamp($url) > 0) {
                $urls[] = $url;
            }
        }
    }

    $urls = array_values(array_unique($urls));

    if ($urls === []) {
        logLine('WARN', 'На официальной странице NBA не найдено PDF-ссылок Injury-Report.');
        return [];
    }

    usort($urls, static function (string $a, string $b): int {
        return parseOfficialPdfTimestamp($b) <=> parseOfficialPdfTimestamp($a);
    });

    logLine('INFO', 'Найдено официальных PDF NBA: ' . count($urls) . '. Самый свежий: ' . $urls[0]);

    return $urls;
}

function downloadOfficialPdfBinary(string $url): string
{
    try {
        logLine('INFO', 'Пробую скачать PDF через PHP cURL. Timeout: ' . PDF_DOWNLOAD_TIMEOUT . ' сек.');

        $result = request($url, 'GET', [
            'connect_timeout' => PDF_CONNECT_TIMEOUT,
            'timeout' => PDF_DOWNLOAD_TIMEOUT,
            'retries' => 1,
            'headers' => [
                'Referer: ' . OFFICIAL_REPORT_PAGE,
                'Accept: application/pdf,application/octet-stream,*/*;q=0.8',
            ],
        ]);

        $body = (string)($result['body'] ?? '');

        if (looksLikePdf($body)) {
            return $body;
        }

        logLine('WARN', 'PHP cURL получил ответ, но это не похоже на PDF. Размер ответа: ' . strlen($body) . ' байт. Пробую fallback через системный curl.');
    } catch (Throwable $e) {
        logLine('WARN', 'PHP cURL не скачал PDF: ' . $e->getMessage() . '. Пробую fallback через системный curl.');
    }

    $fallback = downloadWithSystemCurl($url);
    if (looksLikePdf($fallback)) {
        return $fallback;
    }

    throw new RuntimeException('Не удалось скачать официальный PDF NBA даже через fallback curl: ' . $url);
}

function downloadWithSystemCurl(string $url): string
{
    if (!function_exists('shell_exec')) {
        logLine('WARN', 'shell_exec недоступен, fallback через системный curl невозможен.');
        return '';
    }

    $tmp = tempnam(sys_get_temp_dir(), 'nba_pdf_');
    if ($tmp === false) {
        logLine('WARN', 'Не удалось создать временный файл для PDF.');
        return '';
    }

    $cmd = implode(' ', [
        'curl',
        '--location',
        '--fail',
        '--silent',
        '--show-error',
        '--compressed',
        '--ipv4',
        '--connect-timeout', (string)PDF_CONNECT_TIMEOUT,
        '--max-time', (string)PDF_DOWNLOAD_TIMEOUT,
        '--retry', '2',
        '--retry-delay', '2',
        '--user-agent', escapeshellarg(USER_AGENT),
        '--header', escapeshellarg('Referer: ' . OFFICIAL_REPORT_PAGE),
        '--header', escapeshellarg('Accept: application/pdf,application/octet-stream,*/*;q=0.8'),
        '--output', escapeshellarg($tmp),
        escapeshellarg($url),
        '2>&1',
    ]);

    logLine('INFO', 'Пробую скачать PDF через системный curl. Timeout: ' . PDF_DOWNLOAD_TIMEOUT . ' сек.');
    $output = (string)@shell_exec($cmd);

    if (!is_file($tmp) || filesize($tmp) === 0) {
        @unlink($tmp);
        logLine('WARN', 'Системный curl не скачал PDF. Ответ curl: ' . trim($output));
        return '';
    }

    $body = (string)file_get_contents($tmp);
    @unlink($tmp);

    if (!looksLikePdf($body)) {
        logLine('WARN', 'Системный curl скачал ответ, но это не PDF. Размер: ' . strlen($body) . ' байт. Ответ curl: ' . trim($output));
        return '';
    }

    return $body;
}

function looksLikePdf(string $body): bool
{
    if (strlen($body) < 1000) {
        return false;
    }

    return str_contains(substr($body, 0, 1024), '%PDF');
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
            'connect_timeout' => 5,
            'timeout' => 8,
            'allow_status' => [200],
            'retries' => 1,
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

    $dt = DateTimeImmutable::createFromFormat(
        'Y-m-d H:i',
        sprintf('%s %02d:%02d', $m[1], $hour, $minute),
        new DateTimeZone('America/New_York')
    );

    return $dt instanceof DateTimeImmutable ? $dt->getTimestamp() : 0;
}

function parseOfficialPdfText(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    $rows = parseOfficialPdfTextByColumns($text);
    if ($rows !== []) {
        return $rows;
    }

    return parseOfficialPdfTextFallback($text);
}

function parseOfficialPdfTextByColumns(string $text): array
{
    $rawLines = explode("\n", $text);
    $header = '';

    foreach ($rawLines as $line) {
        if (str_contains($line, 'Game Date') && str_contains($line, 'Player Name') && str_contains($line, 'Current Status')) {
            $header = $line;
            break;
        }
    }

    if ($header === '') {
        return [];
    }

    $datePos = strpos($header, 'Game Date');
    $timePos = strpos($header, 'Game Time');
    $matchupPos = strpos($header, 'Matchup');
    $teamPos = strpos($header, 'Team');
    $playerPos = strpos($header, 'Player Name');
    $statusPos = strpos($header, 'Current Status');
    $reasonPos = strpos($header, 'Reason');

    if ($datePos === false || $timePos === false || $matchupPos === false || $teamPos === false || $playerPos === false || $statusPos === false || $reasonPos === false) {
        return [];
    }

    $rows = [];
    $currentGameDate = '';
    $currentGameTime = '';
    $currentMatchup = '';
    $currentTeam = '';
    $pending = null;
    $preReason = [];

    foreach ($rawLines as $rawLine) {
        $line = rtrim($rawLine);
        if (trim($line) === '') {
            continue;
        }

        if (
            str_starts_with(trim($line), 'Injury Report:') ||
            str_starts_with(trim($line), 'Page ') ||
            str_contains($line, 'Game Date')
        ) {
            continue;
        }

        $dateCol = trim(substr($line, $datePos, max(0, $timePos - $datePos)));
        $timeCol = trim(substr($line, $timePos, max(0, $matchupPos - $timePos)));
        $matchupCol = trim(substr($line, $matchupPos, max(0, $teamPos - $matchupPos)));
        $teamCol = trim(substr($line, $teamPos, max(0, $playerPos - $teamPos)));
        $playerCol = trim(substr($line, $playerPos, max(0, $statusPos - $playerPos)));
        $statusCol = trim(substr($line, $statusPos, max(0, $reasonPos - $statusPos)));
        $reasonCol = trim(substr($line, $reasonPos));

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateCol)) {
            $currentGameDate = $dateCol;
        }
        if (preg_match('/^\d{2}:\d{2}\s+\(ET\)$/', $timeCol)) {
            $currentGameTime = $timeCol;
        }
        if (preg_match('/^[A-Z]{2,3}@[A-Z]{2,3}$/', $matchupCol)) {
            $currentMatchup = $matchupCol;
        }
        if (isset(TEAM_ALIASES[$teamCol])) {
            $currentTeam = canonicalTeam($teamCol);
        }

        if ($reasonCol === 'NOT YET SUBMITTED' || $playerCol === 'NOT YET SUBMITTED' || $teamCol === 'NOT YET SUBMITTED') {
            continue;
        }

        $hasPlayer = $playerCol !== '';
        $hasStatus = array_key_exists($statusCol, STATUS_MAP_RU);

        if ($hasPlayer && $hasStatus) {
            if ($pending !== null) {
                $rows[] = finalizeOfficialRow($pending);
                $pending = null;
            }

            $reasonParts = [];
            if ($preReason !== []) {
                $reasonParts = array_merge($reasonParts, $preReason);
                $preReason = [];
            }
            if ($reasonCol !== '') {
                $reasonParts[] = $reasonCol;
            }

            $pending = [
                'player_name' => normalizePlayerName($playerCol),
                'team' => $currentTeam,
                'raw_status' => $statusCol,
                'raw_reason' => trim(implode(' ', $reasonParts)),
                'report_date' => normalizeSlashDate($currentGameDate),
                'game_date' => normalizeSlashDate($currentGameDate),
                'game_time_et' => $currentGameTime,
                'matchup' => $currentMatchup,
            ];

            continue;
        }

        if ($reasonCol !== '') {
            $startsNewReason = preg_match('/^(Injury\/Illness|Illness|Not With Team)\s*-/i', $reasonCol) === 1;
            $pendingReason = $pending !== null ? trim((string)$pending['raw_reason']) : '';
            $pendingNeedsContinuation = $pendingReason !== '' && preg_match('/[;:-]\s*$/u', $pendingReason) === 1;

            if ($pending !== null && (!$startsNewReason || $pendingNeedsContinuation)) {
                $pending['raw_reason'] = trim($pendingReason . ' ' . $reasonCol);
            } else {
                if ($pending !== null) {
                    $rows[] = finalizeOfficialRow($pending);
                    $pending = null;
                }
                $preReason[] = $reasonCol;
            }
        }
    }

    if ($pending !== null) {
        $rows[] = finalizeOfficialRow($pending);
    }

    return dedupeByKey($rows, static fn(array $row): string => buildSourceKey($row['player_name'], $row['team']) . '|' . ($row['report_date'] ?? ''));
}

function parseOfficialPdfTextFallback(string $text): array
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
        if (
            str_starts_with($line, 'Injury Report:') ||
            str_starts_with($line, 'Page ') ||
            str_contains($line, 'Game Date Game Time Matchup Team Player Name Current Status Reason')
        ) {
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
    if (preg_match('/^(Out for season|No timetable|TBD|Day-To-Day|Game-Time Decision|(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2})\b/u', $prefix, $returnMatch)) {
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
        'injury_raw' => trim($prefix),
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
        'injury_raw' => trim($m['injury']),
        'status_hint_raw' => '',
        'report_date' => todayYmd(),
    ];
}

function mergeRows(array $officialRows, array $espnRows, array $cbsRows): array
{
    $espnIndexes = buildSecondaryIndexes($espnRows);
    $cbsIndexes = buildSecondaryIndexes($cbsRows);

    $items = [];
    $stats = [
        'espn_exact' => 0,
        'espn_player_only' => 0,
        'cbs_exact' => 0,
        'cbs_player_only' => 0,
        'fallback' => 0,
    ];

    foreach ($officialRows as $official) {
        $match = findSecondaryMatch($official, $espnIndexes, $cbsIndexes);
        $secondary = $match['row'];
        $returnSource = $match['source'];
        $matchType = $match['match_type'];

        if ($returnSource === 'espn' && $matchType === 'exact') {
            $stats['espn_exact']++;
        } elseif ($returnSource === 'espn' && $matchType === 'player_only') {
            $stats['espn_player_only']++;
        } elseif ($returnSource === 'cbs' && $matchType === 'exact') {
            $stats['cbs_exact']++;
        } elseif ($returnSource === 'cbs' && $matchType === 'player_only') {
            $stats['cbs_player_only']++;
        } else {
            $stats['fallback']++;
        }

        $playerRu = playerRu((string)$official['player_name']);
        $rawStatus = (string)$official['raw_status'];
        $isAvailableOfficial = $rawStatus === 'Available';

        $reasonSourceRaw = enrichReasonRaw((string)$official['raw_reason'], $secondary);
        $reasonInfo = buildReasonRu($reasonSourceRaw);
        $reasonRu = $reasonInfo['reason_ru'];
        $reasonDetailRu = $reasonInfo['reason_detail_ru'];

        // Для статуса Available медицинскую пометку не показываем в колонке "Причина",
        // но сохраняем её для мягкого контекста в комментарии.
        $availableMedicalReasonRu = $reasonRu;
        $availableMedicalReasonSourceRaw = $reasonSourceRaw;

        $returnInfo = buildReturnInfo($secondary, $rawStatus);
        $secondaryCommentRu = translateCommentRu((string)($secondary['comment_raw'] ?? ''));
        $commentRu = buildTableCommentRu($rawStatus, $reasonRu, $playerRu, $secondaryCommentRu);

        // Ручные override-правки не должны перебивать официальный статус Available.
        $manualOverride = $isAvailableOfficial ? null : manualInjuryOverride((string)$official['player_name'], (string)$official['team'], (string)$official['raw_reason'], $rawStatus);
        if ($manualOverride !== null) {
            if (($manualOverride['reason_ru'] ?? '') !== '') {
                $reasonRu = (string)$manualOverride['reason_ru'];
                $reasonDetailRu = (string)($manualOverride['reason_detail_ru'] ?? '');
                $reasonSourceRaw = (string)($manualOverride['reason_source_raw'] ?? $reasonSourceRaw);
            }

            if (($manualOverride['return_or_reevaluation_ru'] ?? '') !== '') {
                $returnInfo['estimated_return_date_raw'] = (string)($manualOverride['estimated_return_date_raw'] ?? 'manual');
                $returnInfo['estimated_return_date_ru'] = (string)$manualOverride['return_or_reevaluation_ru'];
                $returnInfo['reevaluation_raw'] = '';
                $returnInfo['reevaluation_ru'] = '';
                $returnInfo['return_or_reevaluation_ru'] = (string)$manualOverride['return_or_reevaluation_ru'];
            }

            if (($manualOverride['comment_ru'] ?? '') !== '') {
                $commentRu = (string)$manualOverride['comment_ru'];
            }

            $returnSource = 'manual_verified';
            $matchType = $matchType !== '' ? $matchType . '+manual_verified' : 'manual_verified';
        }

        if ($isAvailableOfficial) {
            $reasonRu = '—';
            $reasonDetailRu = '';
            $reasonSourceRaw = '';
            $returnInfo['estimated_return_date_raw'] = '';
            $returnInfo['estimated_return_date_ru'] = 'Готов к игре';
            $returnInfo['reevaluation_raw'] = '';
            $returnInfo['reevaluation_ru'] = '';
            $returnInfo['return_or_reevaluation_ru'] = 'Готов к игре';
            $commentRu = buildAvailableCommentRu($playerRu, $availableMedicalReasonRu, $availableMedicalReasonSourceRaw);
        }

        $sourceComment = $manualOverride !== null ? 'manual_verified' : ($secondary !== null && trim((string)($secondary['comment_raw'] ?? '')) !== '' ? $returnSource : 'fallback');
        $sourceReason = $manualOverride !== null && (($manualOverride['reason_ru'] ?? '') !== '') ? 'manual_verified' : ($secondary !== null && trim((string)($secondary['injury_raw'] ?? $secondary['status_hint_raw'] ?? '')) !== '' ? $returnSource : 'official_nba');

        $items[] = [
            'id' => buildItemId($official['player_name'], $official['team'], $official['report_date']),
            'date' => $official['report_date'],
            'game_date' => $official['game_date'],
            'game_time_et' => $official['game_time_et'],
            'matchup' => $official['matchup'],
            'player' => $official['player_name'],
            'player_ru' => $playerRu,
            'team' => $official['team'],
            'team_ru' => teamRu($official['team']),
            'raw_status' => $official['raw_status'],
            'raw_reason' => $official['raw_reason'],
            'reason_source_raw' => $reasonSourceRaw,
            'raw_comment' => (string)($secondary['comment_raw'] ?? ''),
            'secondary_source' => $secondary['source'] ?? '',
            'secondary_match_type' => $matchType,
            'secondary_status_hint_raw' => (string)($secondary['status_hint_raw'] ?? ''),
            'status_ru' => translateStatusRu($official['raw_status']),
            'reason_ru' => $reasonRu,
            'reason_detail_ru' => $reasonDetailRu,
            'comment_ru' => $commentRu,
            'estimated_return_date_raw' => $returnInfo['estimated_return_date_raw'],
            'estimated_return_date_ru' => $returnInfo['estimated_return_date_ru'],
            'reevaluation_raw' => $returnInfo['reevaluation_raw'],
            'reevaluation_ru' => $returnInfo['reevaluation_ru'],
            'return_or_reevaluation_ru' => $returnInfo['return_or_reevaluation_ru'],
            'source_status' => 'official_nba',
            'source_return' => $returnSource,
            'source_reason' => $sourceReason,
            'source_comment' => $sourceComment,
            'source_enrichment' => $returnSource !== 'fallback' ? [$returnSource] : [],
            'updated_at' => formatMoscowDateTime(),
            'display' => [
                'player' => $playerRu,
                'team' => teamRu($official['team']),
                'status' => translateStatusRu($official['raw_status']),
                'reason' => $reasonRu,
                'reason_detail' => $reasonDetailRu,
                'return_or_reevaluation' => $returnInfo['return_or_reevaluation_ru'],
                'comment' => $commentRu,
            ],
        ];
    }

    $GLOBALS['enrichment_stats'] = $stats;
    logLine('INFO', sprintf(
        'Enrichment merge: ESPN exact=%d, ESPN player_only=%d, CBS exact=%d, CBS player_only=%d, fallback=%d',
        $stats['espn_exact'],
        $stats['espn_player_only'],
        $stats['cbs_exact'],
        $stats['cbs_player_only'],
        $stats['fallback']
    ));

    return $items;
}

function buildSecondaryIndexes(array $rows): array
{
    $byExact = [];
    $byPlayer = [];

    foreach ($rows as $row) {
        $exactKey = buildSourceKey((string)$row['player_name'], (string)$row['team']);
        $byExact[$exactKey] = $row;

        $playerKey = buildPlayerMatchKey((string)$row['player_name']);
        if ($playerKey !== '') {
            $byPlayer[$playerKey][] = $row;
        }
    }

    return [
        'exact' => $byExact,
        'player' => $byPlayer,
    ];
}

function findSecondaryMatch(array $official, array $espnIndexes, array $cbsIndexes): array
{
    $key = buildSourceKey((string)$official['player_name'], (string)$official['team']);

    if (isset($espnIndexes['exact'][$key])) {
        return ['row' => $espnIndexes['exact'][$key], 'source' => 'espn', 'match_type' => 'exact'];
    }

    if (isset($cbsIndexes['exact'][$key])) {
        return ['row' => $cbsIndexes['exact'][$key], 'source' => 'cbs', 'match_type' => 'exact'];
    }

    $playerKey = buildPlayerMatchKey((string)$official['player_name']);
    $team = canonicalTeam((string)$official['team']);

    $espnPlayerMatch = pickUniqueSecondaryPlayerMatch($espnIndexes['player'][$playerKey] ?? [], $team);
    if ($espnPlayerMatch !== null) {
        return ['row' => $espnPlayerMatch, 'source' => 'espn', 'match_type' => 'player_only'];
    }

    $cbsPlayerMatch = pickUniqueSecondaryPlayerMatch($cbsIndexes['player'][$playerKey] ?? [], $team);
    if ($cbsPlayerMatch !== null) {
        return ['row' => $cbsPlayerMatch, 'source' => 'cbs', 'match_type' => 'player_only'];
    }

    return ['row' => null, 'source' => 'fallback', 'match_type' => 'none'];
}

function pickUniqueSecondaryPlayerMatch(array $rows, string $officialTeam): ?array
{
    if ($rows === []) {
        return null;
    }

    $sameTeam = [];
    foreach ($rows as $row) {
        if (canonicalTeam((string)($row['team'] ?? '')) === $officialTeam) {
            $sameTeam[] = $row;
        }
    }

    if (count($sameTeam) === 1) {
        return $sameTeam[0];
    }

    if (count($rows) === 1) {
        return $rows[0];
    }

    return null;
}

function enrichReasonRaw(string $officialReason, ?array $secondary): string
{
    // Статус и причина в таблице должны идти от официального отчёта лиги.
    // ESPN/CBS оставляем для сроков и дополнительного контекста, но не даём им
    // перетирать медицинскую причину: иначе можно получить устаревшую «болезнь»
    // вместо свежего ушиба/травмы из NBA report.
    $official = cleanupOfficialReasonText($officialReason);
    if ($official !== '' && $official !== '-') {
        return $officialReason;
    }

    $secondaryReason = trim((string)($secondary['injury_raw'] ?? $secondary['status_hint_raw'] ?? ''));
    $secondaryReason = cleanupOfficialReasonText($secondaryReason);

    return $secondaryReason !== '' ? $secondaryReason : $officialReason;
}

function hasKnownBodyPartInReason(string $reason): bool
{
    $clean = cleanupOfficialReasonText($reason);
    $lower = mb_strtolower($clean, 'UTF-8');

    return findBodyPartInfo($lower) !== null;
}

function buildReturnInfo(?array $secondary, string $rawStatus): array
{
    $estimatedReturnRaw = trim((string)($secondary['estimated_return_raw'] ?? ''));
    $commentRaw = trim((string)($secondary['comment_raw'] ?? ''));
    $estimatedReturnRu = extractReturnDateRu($estimatedReturnRaw, $commentRaw);
    $reevaluationRu = extractReevaluationRu($commentRaw);

    $fallbackReturn = fallbackReturnRu($rawStatus);

    return [
        'estimated_return_date_raw' => $estimatedReturnRaw,
        'estimated_return_date_ru' => $estimatedReturnRu !== '' ? $estimatedReturnRu : $fallbackReturn,
        'reevaluation_raw' => $reevaluationRu !== '' ? $commentRaw : '',
        'reevaluation_ru' => $reevaluationRu,
        'return_or_reevaluation_ru' => $reevaluationRu !== '' ? $reevaluationRu : ($estimatedReturnRu !== '' ? $estimatedReturnRu : $fallbackReturn),
    ];
}

function manualInjuryOverride(string $playerName, string $team, string $rawReason = '', string $rawStatus = ''): ?array
{
    $playerKey = slugify(normalizePlayerName($playerName));
    $teamKey = slugify(canonicalTeam($team));
    $reasonLower = mb_strtolower(cleanupOfficialReasonText($rawReason), 'UTF-8');

    if ($playerKey === 'joel-embiid' && $teamKey === 'philadelphia-76ers' && str_contains($reasonLower, 'right hip') && str_contains($reasonLower, 'contusion')) {
        return [
            'reason_ru' => 'Ушиб правого бедра',
            'reason_detail_ru' => 'ушиб',
            'reason_source_raw' => $rawReason,
            'comment_ru' => 'Эмбиид ожидается в составе. В свежем отчёте лиги указана причина: ушиб правого бедра. Ранее у него также было отмечено восстановление после операции по удалению аппендикса. Финальный статус стоит проверить перед матчем.',
        ];
    }

    if ($playerKey === 'jeremy-sochan' && $teamKey === 'new-york-knicks' && str_contains($reasonLower, 'left hamstring')) {
        return [
            'reason_ru' => 'Травма левого бедра',
            'reason_detail_ru' => '',
            'reason_source_raw' => $rawReason,
            'comment_ru' => 'Участие Сохана под вопросом. В свежем отчёте лиги указана причина: травма левого бедра. Финальный статус стоит проверить перед матчем.',
        ];
    }

    $overrides = [
        // Проверенный ручной слой для крупных травм, которые часто не попадают в краткий NBA injury report.
        // Статус NBA не перебиваем, но уточняем причину/срок/комментарий для интерфейса.
        'donte-divincenzo|minnesota-timberwolves' => [
            'reason_ru' => 'Разрыв правого ахилла',
            'reason_detail_ru' => 'разрыв ахилла',
            'reason_source_raw' => 'Right Achilles tendon rupture',
            'return_or_reevaluation_ru' => 'До конца сезона',
            'estimated_return_date_raw' => 'out for season',
            'comment_ru' => 'Не сыграет до конца сезона; ожидается длительное восстановление после разрыва правого ахилла.',
        ],
    ];

    return $overrides[$playerKey . '|' . $teamKey] ?? $overrides[$playerKey . '|'] ?? null;
}

function fallbackReturnRu(string $rawStatus): string
{
    return match ($rawStatus) {
        'Out' => 'Срок возвращения не указан',
        'Questionable', 'Game-Time Decision' => 'Решение ближе к матчу',
        'Doubtful' => 'Вероятность участия низкая',
        'Probable' => 'Ожидается в игре',
        'Available' => 'Готов к игре',
        default => 'Срок уточняется',
    };
}

function buildAvailableCommentRu(string $playerRu, string $medicalReasonRu = '', string $reasonSourceRaw = ''): string
{
    $subject = playerShortRu($playerRu);
    $subject = $subject !== '' ? $subject : 'Игрок';

    $medicalReasonRu = trim($medicalReasonRu);
    if ($medicalReasonRu === '' || $medicalReasonRu === '—' || $medicalReasonRu === 'Причина уточняется') {
        return $subject . ' доступен к матчу.';
    }

    $area = medicalAreaForAvailableRu($reasonSourceRaw, $medicalReasonRu);
    if ($area !== '') {
        return $subject . ' доступен к матчу. В отчёте лиги есть медицинская пометка ' . $area . ', но ограничений по участию не заявлено.';
    }

    return $subject . ' доступен к матчу. В отчёте лиги есть медицинская пометка, но ограничений по участию не заявлено.';
}

function medicalAreaForAvailableRu(string $reasonSourceRaw, string $medicalReasonRu): string
{
    $raw = cleanupOfficialReasonText($reasonSourceRaw);
    $lowerRaw = mb_strtolower($raw, 'UTF-8');
    $body = findBodyPartInfo($lowerRaw);
    $side = findSideInfo($lowerRaw);

    if ($body !== null) {
        return 'по ' . buildBodyPrepositionalPhrase($body, $side);
    }

    $reasonLower = mb_strtolower(trim($medicalReasonRu), 'UTF-8');
    $patterns = [
        '/правой голени/u' => 'по правой голени',
        '/левой голени/u' => 'по левой голени',
        '/правого бедра/u' => 'по правому бедру',
        '/левого бедра/u' => 'по левому бедру',
        '/правого колена/u' => 'по правому колену',
        '/левого колена/u' => 'по левому колену',
        '/правой стопы/u' => 'по правой стопе',
        '/левой стопы/u' => 'по левой стопе',
        '/правого голеностопа/u' => 'по правому голеностопу',
        '/левого голеностопа/u' => 'по левому голеностопу',
        '/правого ахилла/u' => 'по правому ахиллу',
        '/левого ахилла/u' => 'по левому ахиллу',
        '/поясницы/u' => 'по пояснице',
        '/спины/u' => 'по спине',
        '/шеи/u' => 'по шее',
    ];

    foreach ($patterns as $pattern => $phrase) {
        if (preg_match($pattern, $reasonLower)) {
            return $phrase;
        }
    }

    return '';
}

function buildBodyPrepositionalPhrase(array $body, ?string $side): string
{
    $gender = (string)$body['gender'];
    $bodyPhrase = bodyPrepositionalRu((string)$body['genitive']);

    if ($side === 'bilateral') {
        return 'обеим сторонам: ' . $bodyPhrase;
    }

    $sideWord = sidePrepositionalAdjectiveRu($side, $gender);
    return trim($sideWord . ' ' . $bodyPhrase);
}

function bodyPrepositionalRu(string $genitive): string
{
    return match ($genitive) {
        'поясницы' => 'пояснице',
        'голеностопа' => 'голеностопу',
        'голени' => 'голени',
        'большеберцовой кости' => 'большеберцовой кости',
        'бедра' => 'бедру',
        'икроножной мышцы' => 'икроножной мышце',
        'четырёхглавой мышцы бедра' => 'четырёхглавой мышце бедра',
        'приводящей мышцы' => 'приводящей мышце',
        'косой мышцы корпуса' => 'косой мышце корпуса',
        'паха' => 'паху',
        'колена' => 'колену',
        'стопы' => 'стопе',
        'пальца ноги' => 'пальцу ноги',
        'спины' => 'спине',
        'плеча' => 'плечу',
        'локтя' => 'локтю',
        'запястья' => 'запястью',
        'кисти' => 'кисти',
        'пальца руки' => 'пальцу руки',
        'большого пальца руки' => 'большому пальцу руки',
        'шеи' => 'шее',
        'ахилла' => 'ахиллу',
        'рёбер' => 'рёбрам',
        'лица' => 'лицу',
        'носа' => 'носу',
        'глаза' => 'глазу',
        'головы' => 'голове',
        'мышц корпуса' => 'мышцам корпуса',
        default => $genitive,
    };
}

function sidePrepositionalAdjectiveRu(?string $side, string $gender): string
{
    if ($side === null || $gender === 'pl') {
        return '';
    }

    return match ($side . ':' . $gender) {
        'left:m', 'left:n' => 'левому',
        'left:f' => 'левой',
        'right:m', 'right:n' => 'правому',
        'right:f' => 'правой',
        default => '',
    };
}

function buildTableCommentRu(string $rawStatus, string $reasonRu, string $playerRu, string $secondaryCommentRu = ''): string
{
    $subject = playerShortRu($playerRu);
    $subject = $subject !== '' ? $subject : 'Игрок';
    $subjectGenitive = playerShortGenitiveRu($subject);

    $reasonRu = trim($reasonRu);
    $hasReason = $reasonRu !== '' && $reasonRu !== 'Причина уточняется';
    $isIllness = in_array($reasonRu, ['Болезнь', 'Симптомы болезни'], true);

    if ($isIllness) {
        $illnessDetails = 'Тип болезни не раскрыт; дополнительные проверенные источники не подтверждают конкретный диагноз.';

        return match ($rawStatus) {
            'Questionable', 'Game-Time Decision' => 'Участие ' . $subjectGenitive . ' под вопросом из-за болезни. ' . $illnessDetails . ' Финальный статус стоит проверить перед матчем.',
            'Probable' => $subject . ' ожидается в составе. В отчёте лиги указана болезнь. ' . $illnessDetails . ' Финальный статус стоит проверить перед матчем.',
            'Available' => buildAvailableCommentRu($playerRu, $reasonRu, ''),
            'Out' => $subject . ' не сыграет из-за болезни. ' . $illnessDetails,
            'Doubtful' => 'Участие ' . $subjectGenitive . ' маловероятно из-за болезни. ' . $illnessDetails . ' Финальный статус стоит проверить перед матчем.',
            default => 'В отчёте лиги указана болезнь. ' . $illnessDetails,
        };
    }

    $reasonSentence = $hasReason ? ' В свежем отчёте лиги указана причина: ' . lowerFirstRu($reasonRu) . '.' : '';
    $secondaryCommentRu = trim($secondaryCommentRu);

    return match ($rawStatus) {
        'Questionable', 'Game-Time Decision' => 'Участие ' . $subjectGenitive . ' под вопросом.' . $reasonSentence . ' Финальный статус стоит проверить перед матчем.',
        'Probable' => $subject . ' ожидается в составе.' . $reasonSentence . ' Финальный статус стоит проверить перед матчем.',
        'Available' => buildAvailableCommentRu($playerRu, $reasonRu, ''),
        'Doubtful' => 'Участие ' . $subjectGenitive . ' маловероятно.' . $reasonSentence . ' Финальный статус стоит проверить перед матчем.',
        'Out' => buildOutCommentRu($subject, $reasonSentence, $secondaryCommentRu),
        default => $hasReason ? 'В свежем отчёте лиги указана причина: ' . lowerFirstRu($reasonRu) . '.' : 'Дополнительные детали в отчёте лиги не указаны.',
    };
}

function buildOutCommentRu(string $subject, string $reasonSentence, string $secondaryCommentRu): string
{
    $base = $subject . ' не сыграет.' . $reasonSentence;

    if ($secondaryCommentRu === '') {
        return $base . ' Срок возвращения не указан.';
    }

    $secondary = ensureSentenceRu($secondaryCommentRu);
    if ($secondary === 'Не сыграет.') {
        return $base . ' Срок возвращения не указан.';
    }

    return $base . ' ' . $secondary;
}

function fallbackCommentRu(string $rawStatus, string $reasonRu, string $playerRu = 'Игрок'): string
{
    return buildTableCommentRu($rawStatus, $reasonRu, $playerRu);
}

function reasonSentenceRu(string $reasonRu): string
{
    $reasonRu = trim($reasonRu);
    if ($reasonRu === '' || $reasonRu === 'Причина уточняется') {
        return '';
    }

    if ($reasonRu === 'Болезнь') {
        return ' В отчёте лиги указана болезнь. Тип болезни не раскрыт; дополнительные проверенные источники не подтверждают конкретный диагноз.';
    }

    return ' Причина: ' . lowerFirstRu($reasonRu) . '.';
}

function medicalMarkerSentenceRu(string $reasonRu): string
{
    $reasonRu = trim($reasonRu);
    if ($reasonRu === '' || $reasonRu === 'Причина уточняется') {
        return '';
    }

    if ($reasonRu === 'Болезнь') {
        return ' В отчёте лиги указана болезнь. Тип болезни не раскрыт; дополнительные проверенные источники не подтверждают конкретный диагноз.';
    }

    return ' В свежем отчёте лиги указана причина: ' . lowerFirstRu($reasonRu) . '.';
}

function buildPayload(array $items, int $officialCount, int $espnCount, int $cbsCount, ?string $officialPdfUrl): array
{
    $updatedAt = formatMoscowDateTime();

    return [
        'site_title' => 'Травмы игроков NBA',
        'last_updated' => $updatedAt,
        'status' => 'ok',
        'source_label' => 'NBA + ESPN/CBS',
        'data_state_label' => 'Актуальные данные',
        'feed_mode_label' => 'Последний отчёт НБА',
        'meta' => [
            'timezone' => 'Europe/Moscow',
            'generated_at' => $updatedAt,
            'schema_version' => '1.2.0',
            'primary_status_source' => 'official_nba',
            'return_source_priority' => ['espn', 'cbs', 'fallback_by_status'],
            'translation_mode' => 'ru_reason_with_external_context_and_player_dictionary',
            'status_map' => STATUS_MAP_RU,
            'notes' => [
                'Официальный NBA injury report — основной источник правды для статуса и причины.',
                'ESPN используется для даты возврата, повторного осмотра и комментария, если данные доступны.',
                'CBS используется как fallback, если у ESPN нет return info.',
                'Для крупных долгосрочных травм может применяться ручной проверенный override, не меняющий официальный NBA-статус.',
                'Если ESPN/CBS недоступны, сроки и комментарии не выдумываются: используется честный fallback по official NBA status.',
                'Оригинальные поля NBA сохраняются в raw_reason/raw_comment.',
            ],
            'stats' => [
                'official_pdf_url' => $officialPdfUrl,
                'official_rows' => $officialCount,
                'espn_rows' => $espnCount,
                'cbs_rows' => $cbsCount,
                'enrichment_merge' => $GLOBALS['enrichment_stats'] ?? [],
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

function httpGet(string $url): string
{
    return request($url)['body'];
}

function httpGetBinary(string $url): string
{
    return downloadOfficialPdfBinary($url);
}

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

            $headers = array_merge([
                'Accept: text/html,application/pdf,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,ru;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ], $options['headers'] ?? []);

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
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
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
                    'header' => implode("\r\n", $headerLines) . "\r\n",
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
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

    $html = str_replace(
        ['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>', '</tr>', '</h1>', '</h2>', '</h3>', '</h4>'],
        "\n",
        $html
    );

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

function playerRu(string $name): string
{
    $normalized = normalizePlayerName($name);
    $withoutSuffixNoise = preg_replace('/\s+\(.*?\)$/u', '', $normalized) ?? $normalized;

    $externalMap = playerRuExternalMap();

    if (isset($externalMap[$normalized])) {
        return $externalMap[$normalized];
    }

    if (isset($externalMap[$withoutSuffixNoise])) {
        return $externalMap[$withoutSuffixNoise];
    }

    if (isset(PLAYER_RU_MAP[$normalized])) {
        return PLAYER_RU_MAP[$normalized];
    }

    if (isset(PLAYER_RU_MAP[$withoutSuffixNoise])) {
        return PLAYER_RU_MAP[$withoutSuffixNoise];
    }

    return transliteratePlayerNameRu($normalized);
}

function playerRuExternalMap(): array
{
    static $map = null;

    if ($map !== null) {
        return $map;
    }

    $map = [];
    $file = DATA_DIR . '/player_names_ru.json';

    if (!is_file($file)) {
        return $map;
    }

    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return $map;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $map;
    }

    foreach ($decoded as $englishName => $russianName) {
        if (!is_string($englishName) || !is_string($russianName)) {
            continue;
        }

        $englishName = normalizePlayerName($englishName);
        $russianName = trim($russianName);

        if ($englishName === '' || $russianName === '') {
            continue;
        }

        $map[$englishName] = $russianName;

        $withoutSuffixNoise = preg_replace('/\s+\(.*?\)$/u', '', $englishName) ?? $englishName;
        if ($withoutSuffixNoise !== '') {
            $map[$withoutSuffixNoise] = $russianName;
        }
    }

    return $map;
}

function transliteratePlayerNameRu(string $name): string
{
    $manualWords = [
        'AJ' => 'Эй Джей', 'CJ' => 'Си Джей', 'DJ' => 'Ди Джей', 'PJ' => 'Пи Джей', 'RJ' => 'Ар Джей', 'TJ' => 'Ти Джей',
        'Jr.' => 'мл.', 'Jr' => 'мл.', 'Sr.' => 'ст.', 'Sr' => 'ст.',
        'II' => 'II', 'III' => 'III', 'IV' => 'IV',
        'Jalen' => 'Джейлен', 'Jaylen' => 'Джейлен', 'Jaden' => 'Джейден', 'Jabari' => 'Джабари', 'Jaime' => 'Хайме',
        'Jordan' => 'Джордан', 'Josh' => 'Джош', 'Jonathan' => 'Джонатан', 'John' => 'Джон', 'Johnny' => 'Джонни',
        'Anthony' => 'Энтони', 'Kevin' => 'Кевин', 'Devin' => 'Девин', 'Damian' => 'Дэмиан', 'Donovan' => 'Донован',
        'Tyrese' => 'Тайриз', 'Tyler' => 'Тайлер', 'Trey' => 'Трей', 'Trae' => 'Трей', 'Tre' => 'Тре',
        'Isaiah' => 'Айзейя', 'Immanuel' => 'Иммануэль', 'Austin' => 'Остин', 'Victor' => 'Виктор',
        'Fred' => 'Фред', 'Luka' => 'Лука', 'Nikola' => 'Никола', 'Giannis' => 'Яннис', 'Joel' => 'Джоэл',
        'Stephen' => 'Стефен', 'Steven' => 'Стивен', 'LeBron' => 'Леброн', 'Kawhi' => 'Кавай', 'James' => 'Джеймс',
        'Paul' => 'Пол', 'George' => 'Джордж', 'Michael' => 'Майкл', 'Mark' => 'Марк', 'Marcus' => 'Маркус',
        'RayJ' => 'Рэй Джей', 'Norman' => 'Норман', 'Russell' => 'Расселл', 'Chet' => 'Чет', 'Bam' => 'Бэм',
        'Zion' => 'Зайон', 'Scottie' => 'Скотти', 'Darius' => 'Дариус', 'Derrick' => 'Деррик', 'Dillon' => 'Диллон',
        'Max' => 'Макс', 'Maxi' => 'Макси', 'Kyle' => 'Кайл', 'Kelly' => 'Келли', 'Rudy' => 'Руди', 'Rui' => 'Руи',
    ];

    $parts = preg_split('/([\s\-\.\']+)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts)) {
        return simpleLatinToCyrillic($name);
    }

    $out = '';
    foreach ($parts as $part) {
        if (preg_match('/^[\s\-\.\']+$/u', $part)) {
            $out .= str_replace("'", '', $part);
            continue;
        }

        if (isset($manualWords[$part])) {
            $out .= $manualWords[$part];
            continue;
        }

        $out .= simpleLatinToCyrillic($part);
    }

    $out = preg_replace('/\s+/u', ' ', $out) ?? $out;
    $out = trim($out, " \t\n\r\0\x0B-.");

    return $out !== '' ? $out : $name;
}

function simpleLatinToCyrillic(string $word): string
{
    $original = $word;
    $word = mb_strtolower($word, 'UTF-8');

    $rules = [
        'sch' => 'ш', 'sh' => 'ш', 'ch' => 'ч', 'tch' => 'ч', 'zh' => 'ж', 'ph' => 'ф', 'th' => 'т',
        'ck' => 'к', 'qu' => 'кв', 'wh' => 'в', 'wr' => 'р', 'kn' => 'н',
        'ee' => 'и', 'ea' => 'и', 'oo' => 'у', 'ou' => 'ау', 'ow' => 'оу',
        'ay' => 'эй', 'ai' => 'эй', 'ey' => 'и', 'ei' => 'эй', 'ie' => 'и',
        'ya' => 'я', 'yo' => 'йо', 'yu' => 'ю', 'ye' => 'е',
        'j' => 'дж', 'x' => 'кс', 'w' => 'у',
        'a' => 'а', 'b' => 'б', 'c' => 'к', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г', 'h' => 'х',
        'i' => 'и', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п', 'q' => 'к',
        'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'v' => 'в', 'y' => 'и', 'z' => 'з',
    ];

    $result = strtr($word, $rules);
    $result = preg_replace('/[^а-яё0-9]+/u', '', $result) ?? $result;

    if ($result === '') {
        return $original;
    }

    return upperFirst($result);
}

function buildReasonRu(string $reason): array
{
    $raw = trim($reason);
    if ($raw === '' || $raw === '-') {
        return ['reason_ru' => 'Причина уточняется', 'reason_detail_ru' => ''];
    }

    $text = cleanupOfficialReasonText($raw);
    $lowerRaw = mb_strtolower($raw, 'UTF-8');
    $lower = mb_strtolower($text, 'UTF-8');

    if (str_contains($lowerRaw, 'not with team')) {
        if (str_contains($lowerRaw, 'personal')) {
            return ['reason_ru' => 'Вне расположения команды: личные причины', 'reason_detail_ru' => ''];
        }
        return ['reason_ru' => 'Вне расположения команды', 'reason_detail_ru' => ''];
    }

    if (str_contains($lowerRaw, 'g league')) {
        if (str_contains($lowerRaw, 'two-way')) {
            return ['reason_ru' => 'Джи-Лига: двусторонний контракт', 'reason_detail_ru' => ''];
        }
        if (str_contains($lowerRaw, 'on assignment')) {
            return ['reason_ru' => 'Джи-Лига: командирован', 'reason_detail_ru' => ''];
        }
        return ['reason_ru' => 'Джи-Лига', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['flu-like', 'flu like', 'flu symptoms'])) {
        return ['reason_ru' => 'Симптомы болезни', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['illness', 'non-covid illness', 'sick', 'flu', 'virus', 'viral'])) {
        return ['reason_ru' => 'Болезнь', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['personal'])) {
        return ['reason_ru' => 'Личные причины', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['rest'])) {
        return ['reason_ru' => 'Отдых', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['injury management', 'maintenance', 'load management'])) {
        return ['reason_ru' => 'Контроль нагрузки', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['return to competition reconditioning', 'reconditioning'])) {
        return ['reason_ru' => 'Набор игровых кондиций', 'reason_detail_ru' => ''];
    }

    if (containsAny($lower, ['concussion'])) {
        return ['reason_ru' => 'Сотрясение', 'reason_detail_ru' => ''];
    }

    $body = findBodyPartInfo($lower);
    $side = findSideInfo($lowerRaw);
    $details = findInjuryDetailsRu($lowerRaw);

    if ($body !== null) {
        $detailsForBody = filterDetailsForBodyReason($details);
        $detail = implode(', ', $detailsForBody);
        $reasonRu = buildBodyReasonPhrase($body, $side);
        if ($detail !== '') {
            $fanFriendlyReason = buildBodyDetailReasonPhrase($body, $side, $detail);
            $reasonRu = $fanFriendlyReason !== '' ? $fanFriendlyReason : ($reasonRu . ' — ' . $detail);
        }
        return ['reason_ru' => $reasonRu, 'reason_detail_ru' => $detail];
    }

    $detail = implode(', ', $details);

    if ($details !== []) {
        $reasonRu = upperFirst($detail);
        if (needsUnknownAreaNoteRu($details)) {
            $reasonRu .= ' — без детальных уточнений в отчёте лиги';
        }
        return ['reason_ru' => $reasonRu, 'reason_detail_ru' => $detail];
    }

    return ['reason_ru' => 'Причина уточняется', 'reason_detail_ru' => ''];
}

function cleanupOfficialReasonText(string $reason): string
{
    $reason = trim($reason);
    $reason = preg_replace('/^Injury\/Illness\s*-\s*/i', '', $reason) ?? $reason;
    $reason = preg_replace('/^Illness\s*-\s*/i', '', $reason) ?? $reason;
    $reason = preg_replace('/^Not With Team\s*-\s*/i', '', $reason) ?? $reason;
    $reason = preg_replace('/\s+-\s+/u', '; ', $reason) ?? $reason;
    $reason = preg_replace('/\s*;\s*/u', '; ', $reason) ?? $reason;
    $reason = preg_replace('/\s+/u', ' ', $reason) ?? $reason;
    return trim($reason, " -—;\t\n\r\0\x0B");
}

function findSideInfo(string $lower): ?string
{
    if (preg_match('/\bleft\b/i', $lower)) {
        return 'left';
    }
    if (preg_match('/\bright\b/i', $lower)) {
        return 'right';
    }
    if (preg_match('/\bbilateral\b/i', $lower)) {
        return 'bilateral';
    }
    return null;
}

function findBodyPartInfo(string $lower): ?array
{
    $map = [
        ['keys' => ['lower back'], 'genitive' => 'поясницы', 'gender' => 'f'],
        ['keys' => ['high ankle'], 'genitive' => 'голеностопа', 'gender' => 'm'],
        ['keys' => ['shin splints', 'shin splint', 'splints', 'splint', 'shin'], 'genitive' => 'голени', 'gender' => 'f'],
        ['keys' => ['tibia'], 'genitive' => 'большеберцовой кости', 'gender' => 'f'],
        // Для фанатского интерфейса hamstring показываем как бедро, без перегруза анатомией.
        ['keys' => ['hamstring'], 'genitive' => 'бедра', 'gender' => 'n'],
        ['keys' => ['calf'], 'genitive' => 'икроножной мышцы', 'gender' => 'f'],
        ['keys' => ['quad', 'quadriceps'], 'genitive' => 'четырёхглавой мышцы бедра', 'gender' => 'f'],
        ['keys' => ['adductor'], 'genitive' => 'приводящей мышцы', 'gender' => 'f'],
        ['keys' => ['oblique'], 'genitive' => 'косой мышцы корпуса', 'gender' => 'f'],
        ['keys' => ['groin'], 'genitive' => 'паха', 'gender' => 'm'],
        ['keys' => ['knee'], 'genitive' => 'колена', 'gender' => 'n'],
        ['keys' => ['ankle'], 'genitive' => 'голеностопа', 'gender' => 'm'],
        ['keys' => ['foot'], 'genitive' => 'стопы', 'gender' => 'f'],
        ['keys' => ['toe'], 'genitive' => 'пальца ноги', 'gender' => 'm'],
        ['keys' => ['hip'], 'genitive' => 'бедра', 'gender' => 'n'],
        ['keys' => ['back'], 'genitive' => 'спины', 'gender' => 'f'],
        ['keys' => ['shoulder'], 'genitive' => 'плеча', 'gender' => 'n'],
        ['keys' => ['elbow'], 'genitive' => 'локтя', 'gender' => 'm'],
        ['keys' => ['wrist'], 'genitive' => 'запястья', 'gender' => 'n'],
        ['keys' => ['hand'], 'genitive' => 'кисти', 'gender' => 'f'],
        ['keys' => ['finger'], 'genitive' => 'пальца руки', 'gender' => 'm'],
        ['keys' => ['thumb'], 'genitive' => 'большого пальца руки', 'gender' => 'm'],
        ['keys' => ['neck'], 'genitive' => 'шеи', 'gender' => 'f'],
        ['keys' => ['achilles'], 'genitive' => 'ахилла', 'gender' => 'm'],
        ['keys' => ['rib', 'ribs'], 'genitive' => 'рёбер', 'gender' => 'pl'],
        ['keys' => ['face'], 'genitive' => 'лица', 'gender' => 'n'],
        ['keys' => ['nose'], 'genitive' => 'носа', 'gender' => 'm'],
        ['keys' => ['eye'], 'genitive' => 'глаза', 'gender' => 'm'],
        ['keys' => ['head'], 'genitive' => 'головы', 'gender' => 'f'],
        ['keys' => ['core'], 'genitive' => 'мышц корпуса', 'gender' => 'pl'],
    ];

    foreach ($map as $info) {
        foreach ($info['keys'] as $key) {
            if (preg_match('/\b' . preg_quote($key, '/') . '\b/i', $lower)) {
                return $info;
            }
        }
    }

    return null;
}
function buildBodyDetailReasonPhrase(array $body, ?string $side, string $detail): string
{
    $detail = trim($detail);
    $allowed = [
        'ушиб',
        'растяжение связок',
        'растяжение мышцы',
        'перелом',
        'воспаление',
        'вывих',
        'рассечение',
        'повреждение',
        'повреждение сухожилия',
        'проблемы с сухожилием',
    ];

    if (!in_array($detail, $allowed, true)) {
        return '';
    }

    $genitive = (string)$body['genitive'];
    $gender = (string)$body['gender'];

    if ($side === 'bilateral') {
        return upperFirst($detail) . ' обеих сторон: ' . $genitive;
    }

    $sideWord = sideAdjectiveRu($side, $gender);
    $bodyPhrase = trim($sideWord . ' ' . $genitive);

    return trim(upperFirst($detail) . ' ' . $bodyPhrase);
}

function sideAdjectiveRu(?string $side, string $gender): string
{
    if ($side === null || $gender === 'pl') {
        return '';
    }

    return match ($side . ':' . $gender) {
        'left:m', 'left:n' => 'левого',
        'left:f' => 'левой',
        'right:m', 'right:n' => 'правого',
        'right:f' => 'правой',
        default => '',
    };
}

function buildBodyReasonPhrase(array $body, ?string $side): string
{
    $genitive = (string)$body['genitive'];
    $gender = (string)$body['gender'];

    if ($side === 'bilateral') {
        return 'Травма обеих сторон: ' . $genitive;
    }

    if ($side === null || $gender === 'pl') {
        return 'Травма ' . $genitive;
    }

    $sideWord = sideAdjectiveRu($side, $gender);

    return trim('Травма ' . $sideWord . ' ' . $genitive);
}

function findInjuryDetailsRu(string $lower): array
{
    $detailMap = [
        'shin splints' => 'шинсплинт',
        'shin splint' => 'шинсплинт',
        'splints' => 'шинсплинт',
        'splint' => 'шинсплинт',
        'surgery' => 'после операции',
        'fracture' => 'перелом',
        'broken' => 'перелом',
        'sprain' => 'растяжение связок',
        'strain' => 'растяжение мышцы',
        'contusion' => 'ушиб',
        'bruise' => 'ушиб',
        'soreness' => 'дискомфорт',
        'pain' => 'боль',
        'tightness' => 'зажатость',
        'inflammation' => 'воспаление',
        'tendinitis' => 'воспаление сухожилия',
        'tendonitis' => 'воспаление сухожилия',
        'tendinopathy' => 'проблемы с сухожилием',
        'tendon' => 'повреждение сухожилия',
        'dislocation' => 'вывих',
        'laceration' => 'рассечение',
        'tear' => 'повреждение',
        'muscle' => 'мышечная травма',
    ];

    $details = [];
    foreach ($detailMap as $key => $value) {
        if (preg_match('/\b' . preg_quote($key, '/') . '\b/i', $lower) && !in_array($value, $details, true)) {
            $details[] = $value;
        }
    }

    if (in_array('растяжение мышцы', $details, true)) {
        $details = array_values(array_filter($details, static fn(string $detail): bool => $detail !== 'мышечная травма'));
    }

    return $details;
}

function filterDetailsForBodyReason(array $details): array
{
    // Если часть тела уже указана, не добавляем слабые/размытые уточнения вроде soreness.
    // Было: "Травма правой икроножной мышцы — болезненность".
    // Стало: "Травма правой икроножной мышцы".
    $generic = [
        'болезненность',
        'дискомфорт',
        'боль',
        'зажатость',
        'мышечная травма',
    ];

    return array_values(array_filter($details, static fn(string $detail): bool => !in_array($detail, $generic, true)));
}

function findInjuryDetailRu(string $lower): string
{
    $details = findInjuryDetailsRu($lower);

    return $details[0] ?? '';
}

function needsUnknownAreaNoteRu(array $details): bool
{
    $genericDetails = [
        'растяжение мышцы',
        'растяжение связок',
        'мышечная травма',
        'болезненность',
        'дискомфорт',
        'боль',
        'ушиб',
        'воспаление',
    ];

    foreach ($details as $detail) {
        if (str_contains($detail, 'голени') || str_contains($detail, 'шинсплинт')) {
            return false;
        }
    }

    foreach ($details as $detail) {
        if (in_array($detail, $genericDetails, true)) {
            return true;
        }
    }

    return false;
}
function playerShortRu(string $playerRu): string
{
    $playerRu = trim(preg_replace('/\s+/u', ' ', $playerRu) ?? $playerRu);
    if ($playerRu === '') {
        return '';
    }

    $parts = preg_split('/\s+/u', $playerRu, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts) || $parts === []) {
        return $playerRu;
    }

    $skip = ['мл.', 'ст.', 'jr.', 'sr.', 'ii', 'iii', 'iv', 'v'];
    for ($i = count($parts) - 1; $i >= 0; $i--) {
        $part = trim($parts[$i], " \t\n\r\0\x0B.,");
        if ($part === '') {
            continue;
        }
        if (in_array(mb_strtolower($part, 'UTF-8'), $skip, true)) {
            continue;
        }
        return $part;
    }

    return $playerRu;
}

function playerShortGenitiveRu(string $subject): string
{
    $subject = trim($subject);
    if ($subject === '' || $subject === 'Игрок') {
        return 'игрока';
    }

    $lower = mb_strtolower($subject, 'UTF-8');

    if (preg_match('/ий$/u', $lower)) {
        return mb_substr($subject, 0, -2, 'UTF-8') . 'ия';
    }
    if (preg_match('/й$/u', $lower)) {
        return mb_substr($subject, 0, -1, 'UTF-8') . 'я';
    }
    if (preg_match('/ь$/u', $lower)) {
        return mb_substr($subject, 0, -1, 'UTF-8') . 'я';
    }
    if (preg_match('/[бвгджзклмнпрстфхцчшщ]$/u', $lower)) {
        return $subject . 'а';
    }

    return $subject;
}

function ensureSentenceRu(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    return preg_match('/[.!?]$/u', $value) ? $value : $value . '.';
}

function lowerFirstRu(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $first = mb_substr($value, 0, 1, 'UTF-8');
    $rest = mb_substr($value, 1, null, 'UTF-8');

    return mb_strtolower($first, 'UTF-8') . $rest;
}

function containsAny(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if (str_contains($haystack, mb_strtolower($needle, 'UTF-8'))) {
            return true;
        }
    }
    return false;
}

function canonicalTeam(string $team): string
{
    return TEAM_ALIASES[$team] ?? $team;
}

function teamRu(string $team): string
{
    return TEAM_RU_MAP[canonicalTeam($team)] ?? $team;
}

function translateStatusRu(string $status): string
{
    return STATUS_MAP_RU[$status] ?? $status;
}

function translateReasonRu(string $reason): string
{
    return buildReasonRu($reason)['reason_ru'];
}

function translateCommentRu(string $comment): string
{
    $comment = trim($comment);

    if ($comment === '') {
        return '';
    }

    $cleanComment = preg_replace('/^[A-Z][a-z]{2}\s+\d{1,2}:\s*/', '', $comment) ?? $comment;
    $cleanComment = trim($cleanComment);
    $lower = mb_strtolower($comment, 'UTF-8');

    if (preg_match('/out for (the )?(remainder of the )?season|season is over|done for (the )?season|ruled out for (the )?season/i', $comment)) {
        return 'Не сыграет до конца сезона.';
    }

    if (preg_match('/ruptured? .*achilles|torn .*achilles|achilles .*tear/i', $comment)) {
        return 'Серьёзная травма ахилла; ожидается длительное восстановление.';
    }

    if (preg_match('/Expected to be out until at least\s+([A-Z][a-z]{2}\s+\d{1,2})/i', $comment, $m)) {
        $dateRu = monthDayToRu($m[1]);

        return $dateRu !== '' ? 'Ожидается отсутствие как минимум до ' . $dateRu : 'Срок возвращения уточняется';
    }

    if (preg_match('/will be re-evaluated in one week/i', $comment)) {
        return 'Повторный осмотр через 1 неделю';
    }

    if (preg_match('/will be re-evaluated in two weeks/i', $comment)) {
        return 'Повторный осмотр через 2 недели';
    }

    if (preg_match('/will be re-evaluated/i', $comment)) {
        return 'Планируется повторный осмотр';
    }

    if (preg_match('/expected to miss at least one week/i', $comment)) {
        return 'Ожидается пропуск как минимум одной недели';
    }

    if (preg_match('/expected to miss at least two weeks/i', $comment)) {
        return 'Ожидается пропуск как минимум двух недель';
    }

    if (preg_match('/expected to play|is expected to play|should play|will play/i', $comment)) {
        return 'Ожидается участие, если не будет ухудшения состояния.';
    }

    if (preg_match('/available for/i', $comment)) {
        return 'Игрок доступен к матчу.';
    }

    if (preg_match('/probable for/i', $comment)) {
        return 'Скорее всего сыграет; финальный статус стоит проверить перед матчем.';
    }

    if (preg_match('/is questionable for|questionable for/i', $comment)) {
        if (containsAny($lower, ['illness', 'sick', 'flu'])) {
            return 'Участие под вопросом из-за болезни; решение ожидается ближе к началу матча.';
        }
        return 'Участие под вопросом; решение ожидается ближе к началу матча.';
    }

    if (preg_match('/doubtful for/i', $comment)) {
        return 'Скорее не сыграет; вероятность участия низкая.';
    }

    if (preg_match('/game-time decision/i', $comment)) {
        return 'Решение будет принято ближе к началу матча.';
    }

    if (preg_match('/day-to-day/i', $comment)) {
        return 'Состояние оценивается ежедневно.';
    }

    if (preg_match('/has been ruled out|will not play|out for/i', $comment)) {
        return 'Не сыграет.';
    }

    return '';
}

function extractReturnDateRu(string $estimatedReturnRaw, string $commentRaw): string
{
    $estimatedReturnRaw = trim($estimatedReturnRaw);

    if ($estimatedReturnRaw !== '') {
        $lower = mb_strtolower($estimatedReturnRaw, 'UTF-8');
        if ($lower === 'out for season') {
            return 'До конца сезона';
        }
        if ($lower === 'no timetable' || $lower === 'tbd') {
            return 'Срок не указан';
        }
        if ($lower === 'day-to-day') {
            return 'Состояние оценивается ежедневно';
        }
        if ($lower === 'game-time decision') {
            return 'Решение в день матча';
        }

        $dateRu = monthDayToRu($estimatedReturnRaw);
        if ($dateRu !== '') {
            return $dateRu;
        }
    }

    if (preg_match('/out for (the )?(remainder of the )?season|season is over|done for (the )?season|ruled out for (the )?season/i', $commentRaw)) {
        return 'До конца сезона';
    }

    if (preg_match('/ruptured? .*achilles|torn .*achilles|achilles .*tear/i', $commentRaw)) {
        return 'До конца сезона';
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

    if (preg_match('/re-evaluated/i', $commentRaw)) {
        return 'Планируется повторный осмотр';
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

function buildPlayerMatchKey(string $playerName): string
{
    $name = normalizePlayerName($playerName);
    $name = preg_replace('/\s+\(.*?\)$/u', '', $name) ?? $name;
    $name = preg_replace('/\b(Jr\.?|Sr\.?|II|III|IV|V)\b/i', '', $name) ?? $name;
    $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

    return slugify(trim($name));
}

function buildSourceKey(string $playerName, string $team): string
{
    return buildPlayerMatchKey($playerName) . '|' . slugify(canonicalTeam($team));
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
    $value = transliterateForSlug($value);
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? $value;

    return trim($value, '-');
}

function transliterateForSlug(string $value): string
{
    return strtr($value, [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
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

function getOfficialPdfUrl(): ?string
{
    return $GLOBALS['official_pdf_url'] ?? null;
}

function setOfficialPdfUrl(string $url): void
{
    $GLOBALS['official_pdf_url'] = $url;
}
