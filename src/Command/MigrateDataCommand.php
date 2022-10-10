<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

/** @noinspection PhpUnusedPrivateMethodInspection */
/** @noinspection SqlResolve */

namespace App\Command;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\Enums\EventTypeEnum;
use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Enums\LocaleEnum;
use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\Enums\StateTypeEnum;
use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Entity\Enums\ThemeEnum;
use App\Entity\Field;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/**
 * Migrates existing data from eTraxis 3.
 *
 * @codeCoverageIgnore Will be removed
 *
 * @todo Remove in eTraxis 4.1
 */
#[AsCommand(
    name: 'etraxis:migrate-data',
    description: 'Migrate existing data from eTraxis 3',
)]
class MigrateDataCommand extends Command
{
    protected SymfonyStyle $io;
    protected string $progressName;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrations = [
            'migrateAccounts',
            'migrateProjects',
            'migrateGroups',
            'migrateMembership',
            'migrateTemplates',
            'migrateGroupPerms',
            'migrateStates',
            'migrateRoleTrans',
            'migrateGroupTrans',
            'migrateStateAssignees',
            'migrateFields',
            'migrateFieldPerms',
            'migrateListValues',
            'migrateFloatValues',
            'migrateStringValues',
            'migrateTextValues',
            'migrateRecords',
            'migrateReads',
            'migrateRecordSubscribes',
            'migrateEvents',
            'migrateFieldValues',
            'migrateChanges',
            'migrateComments',
            'migrateAttachments',
            'migrateChildren',
        ];

        $this->io = new SymfonyStyle($input, $output);

        // Check that all email addresses in the 'tbl_accounts' table are unique.
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT email, COUNT(email) AS total FROM tbl_accounts GROUP BY email');
        $results = array_filter($results, fn (array $row) => $row['total'] > 1);

        if (0 !== count($results)) {
            $this->io->error('All email addresses must be unique, but the following emails are used more than once:');

            foreach ($results as $row) {
                $this->io->writeln(sprintf('%s - %s times', $row['email'], $row['total']));
            }

            $this->io->newLine();
            $this->io->writeln('Please update the email addresses as needed and try again.');

            return Command::FAILURE;
        }

        // Migrate data.
        foreach ($migrations as $migration) {
            $this->{$migration}();
        }

        $this->io->success('Data have been successfully imported.');

        return Command::SUCCESS;
    }

    /**
     * Starts the progress output.
     */
    protected function progressStart(string $progressName, int $max = 0): void
    {
        $this->progressName = $progressName;

        $this->io->progressStart($max);
        $this->io->write(["\r\t\t\t\t\t\t\t", $this->progressName]);
    }

    /**
     * Advances the progress output X steps.
     */
    protected function progressAdvance(int $step = 1): void
    {
        $this->io->progressAdvance($step);
        $this->io->write(["\r\t\t\t\t\t\t\t", $this->progressName]);
    }

    /**
     * Finishes the progress output.
     */
    protected function progressFinish(): void
    {
        $this->io->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_accounts' table.
     */
    private function migrateAccounts(): void
    {
        // Locales conversion map.
        $locales = [
            1000 => 'en_US', // English (United States)
            1001 => 'en_GB', // English (Great Britain)
            1002 => 'en_CA', // English (Canada)
            1003 => 'en_AU', // English (Australia)
            1004 => 'en_NZ', // English (New Zealand)
            1010 => 'fr',    // Français
            1020 => 'de',    // Deutsch
            1030 => 'it',    // Italiano
            1040 => 'es',    // Español
            1080 => 'pt_BR', // Português do Brasil
            1090 => 'nl',    // Nederlands
            2020 => 'sv',    // Svenska
            2050 => 'lv',    // Latviešu
            3000 => 'ru',    // Русский
            3030 => 'pl',    // Polski
            3040 => 'cs',    // Čeština
            3060 => 'hu',    // Magyar
            3130 => 'bg',    // Български
            3140 => 'ro',    // Română
            5000 => 'ja',    // 日本語
            6000 => 'tr',    // Türkçe
        ];

        // Themes conversion map.
        /** @var ThemeEnum[] $themes */
        $themes = [
            'Azure'   => ThemeEnum::Azure,
            'Emerald' => ThemeEnum::Emerald,
            'Mars'    => ThemeEnum::Mars,
        ];

        // Timezones conversion map.
        $timezones = [
            1   => 'Africa/Abidjan',
            2   => 'Africa/Accra',
            3   => 'Africa/Addis_Ababa',
            4   => 'Africa/Algiers',
            5   => 'Africa/Asmara',
            6   => 'Africa/Bamako',
            7   => 'Africa/Bangui',
            8   => 'Africa/Banjul',
            9   => 'Africa/Bissau',
            10  => 'Africa/Blantyre',
            11  => 'Africa/Brazzaville',
            12  => 'Africa/Bujumbura',
            13  => 'Africa/Cairo',
            14  => 'Africa/Casablanca',
            15  => 'Africa/Ceuta',
            16  => 'Africa/Conakry',
            17  => 'Africa/Dakar',
            18  => 'Africa/Dar_es_Salaam',
            19  => 'Africa/Djibouti',
            20  => 'Africa/Douala',
            21  => 'Africa/El_Aaiun',
            22  => 'Africa/Freetown',
            23  => 'Africa/Gaborone',
            24  => 'Africa/Harare',
            25  => 'Africa/Johannesburg',
            26  => 'Africa/Juba',
            27  => 'Africa/Kampala',
            28  => 'Africa/Khartoum',
            29  => 'Africa/Kigali',
            30  => 'Africa/Kinshasa',
            31  => 'Africa/Lagos',
            32  => 'Africa/Libreville',
            33  => 'Africa/Lome',
            34  => 'Africa/Luanda',
            35  => 'Africa/Lubumbashi',
            36  => 'Africa/Lusaka',
            37  => 'Africa/Malabo',
            38  => 'Africa/Maputo',
            39  => 'Africa/Maseru',
            40  => 'Africa/Mbabane',
            41  => 'Africa/Mogadishu',
            42  => 'Africa/Monrovia',
            43  => 'Africa/Nairobi',
            44  => 'Africa/Ndjamena',
            45  => 'Africa/Niamey',
            46  => 'Africa/Nouakchott',
            47  => 'Africa/Ouagadougou',
            48  => 'Africa/Porto-Novo',
            49  => 'Africa/Sao_Tome',
            50  => 'Africa/Tripoli',
            51  => 'Africa/Tunis',
            52  => 'Africa/Windhoek',
            53  => 'America/Adak',
            54  => 'America/Anchorage',
            55  => 'America/Anguilla',
            56  => 'America/Antigua',
            57  => 'America/Araguaina',
            58  => 'America/Argentina/Buenos_Aires',
            59  => 'America/Argentina/Catamarca',
            60  => 'America/Argentina/Cordoba',
            61  => 'America/Argentina/Jujuy',
            62  => 'America/Argentina/La_Rioja',
            63  => 'America/Argentina/Mendoza',
            64  => 'America/Argentina/Rio_Gallegos',
            65  => 'America/Argentina/Salta',
            66  => 'America/Argentina/San_Juan',
            67  => 'America/Argentina/San_Luis',
            68  => 'America/Argentina/Tucuman',
            69  => 'America/Argentina/Ushuaia',
            70  => 'America/Aruba',
            71  => 'America/Asuncion',
            72  => 'America/Atikokan',
            73  => 'America/Bahia',
            74  => 'America/Bahia_Banderas',
            75  => 'America/Barbados',
            76  => 'America/Belem',
            77  => 'America/Belize',
            78  => 'America/Blanc-Sablon',
            79  => 'America/Boa_Vista',
            80  => 'America/Bogota',
            81  => 'America/Boise',
            82  => 'America/Cambridge_Bay',
            83  => 'America/Campo_Grande',
            84  => 'America/Cancun',
            85  => 'America/Caracas',
            86  => 'America/Cayenne',
            87  => 'America/Cayman',
            88  => 'America/Chicago',
            89  => 'America/Chihuahua',
            90  => 'America/Costa_Rica',
            91  => 'America/Cuiaba',
            92  => 'America/Curacao',
            93  => 'America/Danmarkshavn',
            94  => 'America/Dawson',
            95  => 'America/Dawson_Creek',
            96  => 'America/Denver',
            97  => 'America/Detroit',
            98  => 'America/Dominica',
            99  => 'America/Edmonton',
            100 => 'America/Eirunepe',
            101 => 'America/El_Salvador',
            102 => 'America/Fortaleza',
            103 => 'America/Glace_Bay',
            104 => 'America/Godthab',
            105 => 'America/Goose_Bay',
            106 => 'America/Grand_Turk',
            107 => 'America/Grenada',
            108 => 'America/Guadeloupe',
            109 => 'America/Guatemala',
            110 => 'America/Guayaquil',
            111 => 'America/Guyana',
            112 => 'America/Halifax',
            113 => 'America/Havana',
            114 => 'America/Hermosillo',
            115 => 'America/Indiana/Indianapolis',
            116 => 'America/Indiana/Knox',
            117 => 'America/Indiana/Marengo',
            118 => 'America/Indiana/Petersburg',
            119 => 'America/Indiana/Tell_City',
            120 => 'America/Indiana/Vevay',
            121 => 'America/Indiana/Vincennes',
            122 => 'America/Indiana/Winamac',
            123 => 'America/Inuvik',
            124 => 'America/Iqaluit',
            125 => 'America/Jamaica',
            126 => 'America/Juneau',
            127 => 'America/Kentucky/Louisville',
            128 => 'America/Kentucky/Monticello',
            129 => 'America/Kralendijk',
            130 => 'America/La_Paz',
            131 => 'America/Lima',
            132 => 'America/Los_Angeles',
            133 => 'America/Lower_Princes',
            134 => 'America/Maceio',
            135 => 'America/Managua',
            136 => 'America/Manaus',
            137 => 'America/Marigot',
            138 => 'America/Martinique',
            139 => 'America/Matamoros',
            140 => 'America/Mazatlan',
            141 => 'America/Menominee',
            142 => 'America/Merida',
            143 => 'America/Metlakatla',
            144 => 'America/Mexico_City',
            145 => 'America/Miquelon',
            146 => 'America/Moncton',
            147 => 'America/Monterrey',
            148 => 'America/Montevideo',
            149 => 'America/Montreal',
            150 => 'America/Montserrat',
            151 => 'America/Nassau',
            152 => 'America/New_York',
            153 => 'America/Nipigon',
            154 => 'America/Nome',
            155 => 'America/Noronha',
            156 => 'America/North_Dakota/Beulah',
            157 => 'America/North_Dakota/Center',
            158 => 'America/North_Dakota/New_Salem',
            159 => 'America/Ojinaga',
            160 => 'America/Panama',
            161 => 'America/Pangnirtung',
            162 => 'America/Paramaribo',
            163 => 'America/Phoenix',
            164 => 'America/Port-au-Prince',
            165 => 'America/Port_of_Spain',
            166 => 'America/Porto_Velho',
            167 => 'America/Puerto_Rico',
            168 => 'America/Rainy_River',
            169 => 'America/Rankin_Inlet',
            170 => 'America/Recife',
            171 => 'America/Regina',
            172 => 'America/Resolute',
            173 => 'America/Rio_Branco',
            174 => 'America/Santa_Isabel',
            175 => 'America/Santarem',
            176 => 'America/Santiago',
            177 => 'America/Santo_Domingo',
            178 => 'America/Sao_Paulo',
            179 => 'America/Scoresbysund',
            180 => 'America/Shiprock',
            181 => 'America/Sitka',
            182 => 'America/St_Barthelemy',
            183 => 'America/St_Johns',
            184 => 'America/St_Kitts',
            185 => 'America/St_Lucia',
            186 => 'America/St_Thomas',
            187 => 'America/St_Vincent',
            188 => 'America/Swift_Current',
            189 => 'America/Tegucigalpa',
            190 => 'America/Thule',
            191 => 'America/Thunder_Bay',
            192 => 'America/Tijuana',
            193 => 'America/Toronto',
            194 => 'America/Tortola',
            195 => 'America/Vancouver',
            196 => 'America/Whitehorse',
            197 => 'America/Winnipeg',
            198 => 'America/Yakutat',
            199 => 'America/Yellowknife',
            200 => 'Antarctica/Casey',
            201 => 'Antarctica/Davis',
            202 => 'Antarctica/DumontDUrville',
            203 => 'Antarctica/Macquarie',
            204 => 'Antarctica/Mawson',
            205 => 'Antarctica/McMurdo',
            206 => 'Antarctica/Palmer',
            207 => 'Antarctica/Rothera',
            208 => 'Antarctica/South_Pole',
            209 => 'Antarctica/Syowa',
            210 => 'Antarctica/Vostok',
            211 => 'Arctic/Longyearbyen',
            212 => 'Asia/Aden',
            213 => 'Asia/Almaty',
            214 => 'Asia/Amman',
            215 => 'Asia/Anadyr',
            216 => 'Asia/Aqtau',
            217 => 'Asia/Aqtobe',
            218 => 'Asia/Ashgabat',
            219 => 'Asia/Baghdad',
            220 => 'Asia/Bahrain',
            221 => 'Asia/Baku',
            222 => 'Asia/Bangkok',
            223 => 'Asia/Beirut',
            224 => 'Asia/Bishkek',
            225 => 'Asia/Brunei',
            226 => 'Asia/Choibalsan',
            227 => 'Asia/Chongqing',
            228 => 'Asia/Colombo',
            229 => 'Asia/Damascus',
            230 => 'Asia/Dhaka',
            231 => 'Asia/Dili',
            232 => 'Asia/Dubai',
            233 => 'Asia/Dushanbe',
            234 => 'Asia/Gaza',
            235 => 'Asia/Harbin',
            236 => 'Asia/Ho_Chi_Minh',
            237 => 'Asia/Hong_Kong',
            238 => 'Asia/Hovd',
            239 => 'Asia/Irkutsk',
            240 => 'Asia/Jakarta',
            241 => 'Asia/Jayapura',
            242 => 'Asia/Jerusalem',
            243 => 'Asia/Kabul',
            244 => 'Asia/Kamchatka',
            245 => 'Asia/Karachi',
            246 => 'Asia/Kashgar',
            247 => 'Asia/Kathmandu',
            248 => 'Asia/Kolkata',
            249 => 'Asia/Krasnoyarsk',
            250 => 'Asia/Kuala_Lumpur',
            251 => 'Asia/Kuching',
            252 => 'Asia/Kuwait',
            253 => 'Asia/Macau',
            254 => 'Asia/Magadan',
            255 => 'Asia/Makassar',
            256 => 'Asia/Manila',
            257 => 'Asia/Muscat',
            258 => 'Asia/Nicosia',
            259 => 'Asia/Novokuznetsk',
            260 => 'Asia/Novosibirsk',
            261 => 'Asia/Omsk',
            262 => 'Asia/Oral',
            263 => 'Asia/Phnom_Penh',
            264 => 'Asia/Pontianak',
            265 => 'Asia/Pyongyang',
            266 => 'Asia/Qatar',
            267 => 'Asia/Qyzylorda',
            268 => 'Asia/Rangoon',
            269 => 'Asia/Riyadh',
            270 => 'Asia/Sakhalin',
            271 => 'Asia/Samarkand',
            272 => 'Asia/Seoul',
            273 => 'Asia/Shanghai',
            274 => 'Asia/Singapore',
            275 => 'Asia/Taipei',
            276 => 'Asia/Tashkent',
            277 => 'Asia/Tbilisi',
            278 => 'Asia/Tehran',
            279 => 'Asia/Thimphu',
            280 => 'Asia/Tokyo',
            281 => 'Asia/Ulaanbaatar',
            282 => 'Asia/Urumqi',
            283 => 'Asia/Vientiane',
            284 => 'Asia/Vladivostok',
            285 => 'Asia/Yakutsk',
            286 => 'Asia/Yekaterinburg',
            287 => 'Asia/Yerevan',
            288 => 'Atlantic/Azores',
            289 => 'Atlantic/Bermuda',
            290 => 'Atlantic/Canary',
            291 => 'Atlantic/Cape_Verde',
            292 => 'Atlantic/Faroe',
            293 => 'Atlantic/Madeira',
            294 => 'Atlantic/Reykjavik',
            295 => 'Atlantic/South_Georgia',
            296 => 'Atlantic/St_Helena',
            297 => 'Atlantic/Stanley',
            298 => 'Australia/Adelaide',
            299 => 'Australia/Brisbane',
            300 => 'Australia/Broken_Hill',
            301 => 'Australia/Currie',
            302 => 'Australia/Darwin',
            303 => 'Australia/Eucla',
            304 => 'Australia/Hobart',
            305 => 'Australia/Lindeman',
            306 => 'Australia/Lord_Howe',
            307 => 'Australia/Melbourne',
            308 => 'Australia/Perth',
            309 => 'Australia/Sydney',
            310 => 'Europe/Amsterdam',
            311 => 'Europe/Andorra',
            312 => 'Europe/Athens',
            313 => 'Europe/Belgrade',
            314 => 'Europe/Berlin',
            315 => 'Europe/Bratislava',
            316 => 'Europe/Brussels',
            317 => 'Europe/Bucharest',
            318 => 'Europe/Budapest',
            319 => 'Europe/Chisinau',
            320 => 'Europe/Copenhagen',
            321 => 'Europe/Dublin',
            322 => 'Europe/Gibraltar',
            323 => 'Europe/Guernsey',
            324 => 'Europe/Helsinki',
            325 => 'Europe/Isle_of_Man',
            326 => 'Europe/Istanbul',
            327 => 'Europe/Jersey',
            328 => 'Europe/Kaliningrad',
            329 => 'Europe/Kiev',
            330 => 'Europe/Lisbon',
            331 => 'Europe/Ljubljana',
            332 => 'Europe/London',
            333 => 'Europe/Luxembourg',
            334 => 'Europe/Madrid',
            335 => 'Europe/Malta',
            336 => 'Europe/Mariehamn',
            337 => 'Europe/Minsk',
            338 => 'Europe/Monaco',
            339 => 'Europe/Moscow',
            340 => 'Europe/Oslo',
            341 => 'Europe/Paris',
            342 => 'Europe/Podgorica',
            343 => 'Europe/Prague',
            344 => 'Europe/Riga',
            345 => 'Europe/Rome',
            346 => 'Europe/Samara',
            347 => 'Europe/San_Marino',
            348 => 'Europe/Sarajevo',
            349 => 'Europe/Simferopol',
            350 => 'Europe/Skopje',
            351 => 'Europe/Sofia',
            352 => 'Europe/Stockholm',
            353 => 'Europe/Tallinn',
            354 => 'Europe/Tirane',
            355 => 'Europe/Uzhgorod',
            356 => 'Europe/Vaduz',
            357 => 'Europe/Vatican',
            358 => 'Europe/Vienna',
            359 => 'Europe/Vilnius',
            360 => 'Europe/Volgograd',
            361 => 'Europe/Warsaw',
            362 => 'Europe/Zagreb',
            363 => 'Europe/Zaporozhye',
            364 => 'Europe/Zurich',
            365 => 'Indian/Antananarivo',
            366 => 'Indian/Chagos',
            367 => 'Indian/Christmas',
            368 => 'Indian/Cocos',
            369 => 'Indian/Comoro',
            370 => 'Indian/Kerguelen',
            371 => 'Indian/Mahe',
            372 => 'Indian/Maldives',
            373 => 'Indian/Mauritius',
            374 => 'Indian/Mayotte',
            375 => 'Indian/Reunion',
            376 => 'Pacific/Apia',
            377 => 'Pacific/Auckland',
            378 => 'Pacific/Chatham',
            379 => 'Pacific/Chuuk',
            380 => 'Pacific/Easter',
            381 => 'Pacific/Efate',
            382 => 'Pacific/Enderbury',
            383 => 'Pacific/Fakaofo',
            384 => 'Pacific/Fiji',
            385 => 'Pacific/Funafuti',
            386 => 'Pacific/Galapagos',
            387 => 'Pacific/Gambier',
            388 => 'Pacific/Guadalcanal',
            389 => 'Pacific/Guam',
            390 => 'Pacific/Honolulu',
            391 => 'Pacific/Johnston',
            392 => 'Pacific/Kiritimati',
            393 => 'Pacific/Kosrae',
            394 => 'Pacific/Kwajalein',
            395 => 'Pacific/Majuro',
            396 => 'Pacific/Marquesas',
            397 => 'Pacific/Midway',
            398 => 'Pacific/Nauru',
            399 => 'Pacific/Niue',
            400 => 'Pacific/Norfolk',
            401 => 'Pacific/Noumea',
            402 => 'Pacific/Pago_Pago',
            403 => 'Pacific/Palau',
            404 => 'Pacific/Pitcairn',
            405 => 'Pacific/Pohnpei',
            406 => 'Pacific/Port_Moresby',
            407 => 'Pacific/Rarotonga',
            408 => 'Pacific/Saipan',
            409 => 'Pacific/Tahiti',
            410 => 'Pacific/Tarawa',
            411 => 'Pacific/Tongatapu',
            412 => 'Pacific/Wake',
            413 => 'Pacific/Wallis',
            414 => 'UTC',
        ];

        // Migrate accounts one by one.
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE users DISABLE KEYS');
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_accounts ORDER BY account_id');

        $this->progressStart('users', count($results));

        foreach ($results as $row) {
            $sql =
                'INSERT INTO users (id, email, password, fullname, description, admin, disabled, account_provider, account_uid, settings) '.
                'VALUES (:id, :email, :password, :fullname, :description, :admin, :disabled, :account_provider, :account_uid, :settings)';

            $this->entityManager->getConnection()->executeStatement($sql, [
                'id'               => $row['account_id'],
                'email'            => $row['email'],
                'password'         => $row['passwd'],
                'fullname'         => $row['fullname'],
                'description'      => $row['description'],
                'admin'            => $row['is_admin'],
                'disabled'         => $row['is_disabled'],
                'account_provider' => $row['is_ldapuser'] ? AccountProviderEnum::LDAP->value : AccountProviderEnum::eTraxis->value,
                'account_uid'      => $row['is_ldapuser'] ? $row['email'] : Uuid::v4()->toRfc4122(),
                'settings'         => json_encode([
                    'locale'   => $locales[$row['locale']] ?? LocaleEnum::FALLBACK->value,
                    'theme'    => $themes[$row['theme_name']]->value ?? ThemeEnum::FALLBACK->value,
                    'timezone' => $timezones[$row['timezone']] ?? 'UTC',
                ]),
            ]);

            $this->progressAdvance();
        }

        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE users ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_projects' table.
     */
    private function migrateProjects(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE projects DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_projects') ?: 1;

        $this->progressStart('projects', $count);

        $sql =
            'INSERT INTO projects (id, name, description, created_at, suspended) '.
            'SELECT project_id, project_name, description, start_time, is_suspended '.
            'FROM tbl_projects ORDER BY project_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE projects ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_groups' table.
     */
    private function migrateGroups(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE `groups` DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_groups') ?: 1;

        $this->progressStart('groups', $count);

        $sql =
            'INSERT INTO `groups` (id, project_id, name, description) '.
            'SELECT group_id, project_id, group_name, description '.
            'FROM tbl_groups ORDER BY group_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE `groups` ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_membership' table.
     */
    private function migrateMembership(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_membership') ?: 1;

        $this->progressStart('membership', $count);

        $sql =
            'INSERT INTO membership (group_id, user_id) '.
            'SELECT group_id, account_id '.
            'FROM tbl_membership ORDER BY group_id, account_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_templates' table.
     */
    private function migrateTemplates(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE templates DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_templates') ?: 1;

        $this->progressStart('templates', $count);

        $sql =
            'INSERT INTO templates (id, project_id, name, prefix, description, locked, critical_age, frozen_time) '.
            'SELECT template_id, project_id, template_name, template_prefix, description, is_locked, critical_age, frozen_time '.
            'FROM tbl_templates ORDER BY template_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE templates ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_group_perms' table.
     */
    private function migrateGroupPerms(): void
    {
        // Permissions conversion map.
        $permissions = [
            0x0001     => TemplatePermissionEnum::CreateIssues,
            0x0002     => TemplatePermissionEnum::EditIssues,
            0x0004     => TemplatePermissionEnum::SuspendIssues,
            0x0008     => TemplatePermissionEnum::ResumeIssues,
            0x0010     => TemplatePermissionEnum::ReassignIssues,
            0x0040     => TemplatePermissionEnum::AddComments,
            0x0080     => TemplatePermissionEnum::AttachFiles,
            0x0100     => TemplatePermissionEnum::DeleteFiles,
            0x0200     => TemplatePermissionEnum::PrivateComments,
            0x0800     => TemplatePermissionEnum::DeleteIssues,
            0x1000     => TemplatePermissionEnum::ManageDependencies,
            0x40000000 => TemplatePermissionEnum::ViewIssues,
        ];

        // Migrate role permissions from 'tbl_templates' one by one.
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_templates ORDER BY template_id');

        $this->progressStart('template_role_permissions', count($results));

        foreach ($results as $row) {
            $sql =
                'INSERT INTO template_role_permissions (template_id, role, permission) '.
                'VALUES (:template_id, :role, :permission)';

            /** @var TemplatePermissionEnum $permission */
            foreach ($permissions as $mask => $permission) {
                if ($row['registered_perm'] & $mask) {
                    $this->entityManager->getConnection()->executeStatement($sql, [
                        'template_id' => $row['template_id'],
                        'role'        => SystemRoleEnum::Anyone->value,
                        'permission'  => $permission->value,
                    ]);
                }

                if ($row['author_perm'] & $mask) {
                    $this->entityManager->getConnection()->executeStatement($sql, [
                        'template_id' => $row['template_id'],
                        'role'        => SystemRoleEnum::Author->value,
                        'permission'  => $permission->value,
                    ]);
                }

                if ($row['responsible_perm'] & $mask) {
                    $this->entityManager->getConnection()->executeStatement($sql, [
                        'template_id' => $row['template_id'],
                        'role'        => SystemRoleEnum::Responsible->value,
                        'permission'  => $permission->value,
                    ]);
                }
            }

            $this->progressAdvance();
        }

        $this->progressFinish();

        // Migrate group permissions one by one.
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT template_id, group_id, perms FROM tbl_group_perms ORDER BY template_id, group_id');

        $this->progressStart('template_group_permissions', count($results));

        foreach ($results as $row) {
            $sql =
                'INSERT INTO template_group_permissions (template_id, group_id, permission) '.
                'VALUES (:template_id, :group_id, :permission)';

            /** @var TemplatePermissionEnum $permission */
            foreach ($permissions as $mask => $permission) {
                if ($row['perms'] & $mask) {
                    $this->entityManager->getConnection()->executeStatement($sql, [
                        'template_id' => $row['template_id'],
                        'group_id'    => $row['group_id'],
                        'permission'  => $permission->value,
                    ]);
                }
            }

            $this->progressAdvance();
        }

        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_states' table.
     */
    private function migrateStates(): void
    {
        // State type conversion map.
        /** @var StateTypeEnum[] $types */
        $types = [
            1 => StateTypeEnum::Initial,
            2 => StateTypeEnum::Intermediate,
            3 => StateTypeEnum::Final,
        ];

        // State responsibility conversion map.
        /** @var StateResponsibleEnum[] $responsibilities */
        $responsibilities = [
            1 => StateResponsibleEnum::Keep,
            2 => StateResponsibleEnum::Assign,
            3 => StateResponsibleEnum::Remove,
        ];

        // Migrate states one by one.
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE states DISABLE KEYS');
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_states ORDER BY state_id');

        $this->progressStart('states', count($results));

        foreach ($results as $row) {
            $sql =
                'INSERT INTO states (id, template_id, name, type, responsible) '.
                'VALUES (:id, :template_id, :name, :type, :responsible)';

            $this->entityManager->getConnection()->executeStatement($sql, [
                'id'          => $row['state_id'],
                'template_id' => $row['template_id'],
                'name'        => $row['state_name'],
                'type'        => $types[$row['state_type']]->value,
                'responsible' => $responsibilities[$row['responsible']]->value,
            ]);

            $this->progressAdvance();
        }

        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE states ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_role_trans' table.
     */
    private function migrateRoleTrans(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_role_trans') ?: 1;

        $this->progressStart('state_role_transitions', $count);

        $sql =
            'INSERT INTO state_role_transitions (from_state_id, to_state_id, role) '.
            'SELECT state_id_from, state_id_to, role '.
            'FROM tbl_role_trans ORDER BY state_id_from, state_id_to, role';

        $this->entityManager->getConnection()->executeStatement($sql);

        $sql = 'UPDATE state_role_transitions SET role = :str_role WHERE role = :int_role';

        $this->entityManager->getConnection()->executeStatement($sql, [
            'int_role' => -1,
            'str_role' => SystemRoleEnum::Author->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'int_role' => -2,
            'str_role' => SystemRoleEnum::Responsible->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'int_role' => -3,
            'str_role' => SystemRoleEnum::Anyone->value,
        ]);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_group_trans' table.
     */
    private function migrateGroupTrans(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_group_trans') ?: 1;

        $this->progressStart('state_group_transitions', $count);

        $sql =
            'INSERT INTO state_group_transitions (from_state_id, to_state_id, group_id) '.
            'SELECT state_id_from, state_id_to, group_id '.
            'FROM tbl_group_trans ORDER BY state_id_from, state_id_to, group_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_state_assignees' table.
     */
    private function migrateStateAssignees(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_state_assignees') ?: 1;

        $this->progressStart('state_responsible_groups', $count);

        $sql =
            'INSERT INTO state_responsible_groups (state_id, group_id) '.
            'SELECT state_id, group_id '.
            'FROM tbl_state_assignees ORDER BY state_id, group_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_fields' table.
     */
    private function migrateFields(): void
    {
        // Field type conversion map.
        /** @var FieldTypeEnum[] $types */
        $types = [
            1 => FieldTypeEnum::Number,
            2 => FieldTypeEnum::String,
            3 => FieldTypeEnum::Text,
            4 => FieldTypeEnum::Checkbox,
            5 => FieldTypeEnum::List,
            6 => FieldTypeEnum::Issue,
            7 => FieldTypeEnum::Date,
            8 => FieldTypeEnum::Duration,
            9 => FieldTypeEnum::Decimal,
        ];

        // Migrate fields.
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE fields DISABLE KEYS');
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_fields ORDER BY field_id');

        $this->progressStart('fields', count($results));

        foreach ($results as $row) {
            $parameters = [];

            switch ($types[$row['field_type']]) {
                case FieldTypeEnum::Number:
                case FieldTypeEnum::Date:
                case FieldTypeEnum::Duration:
                case FieldTypeEnum::Decimal:
                    $parameters[Field::DEFAULT] = $row['value_id'];
                    $parameters[Field::MINIMUM] = $row['param1'];
                    $parameters[Field::MAXIMUM] = $row['param2'];

                    break;

                case FieldTypeEnum::String:
                case FieldTypeEnum::Text:
                    $parameters[Field::DEFAULT]      = $row['value_id'];
                    $parameters[Field::LENGTH]       = $row['param1'];
                    $parameters[Field::PCRE_CHECK]   = $row['regex_check'];
                    $parameters[Field::PCRE_SEARCH]  = $row['regex_search'];
                    $parameters[Field::PCRE_REPLACE] = $row['regex_replace'];

                    break;

                case FieldTypeEnum::Checkbox:
                    $parameters[Field::DEFAULT] = (bool) $row['value_id'];

                    break;

                case FieldTypeEnum::List:
                    $parameters[Field::DEFAULT] = $row['value_id'];

                    break;

                case FieldTypeEnum::Issue:
                    break;
            }

            $sql =
                'INSERT INTO fields (id, state_id, name, type, description, position, required, removed_at, parameters) '.
                'VALUES (:id, :state_id, :name, :type, :description, :position, :required, :removed_at, :parameters)';

            $this->entityManager->getConnection()->executeStatement($sql, [
                'id'          => $row['field_id'],
                'state_id'    => $row['state_id'],
                'name'        => $row['field_name'],
                'type'        => $types[$row['field_type']]->value,
                'description' => $row['description'],
                'position'    => $row['field_order'],
                'required'    => $row['is_required'],
                'removed_at'  => $row['removal_time'] ?: null,
                'parameters'  => json_encode($parameters),
            ]);

            $this->progressAdvance();
        }

        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE fields ENABLE KEYS');

        // Migrate role permissions.
        $this->progressStart('field_role_permissions', count($results));

        $sql =
            'INSERT INTO field_role_permissions (field_id, role, permission) '.
            'SELECT field_id, :new_role, :new_permission '.
            'FROM tbl_fields WHERE :old_role = :old_permission '.
            'ORDER BY field_id';

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_role'       => 'registered_perm',
            'new_role'       => SystemRoleEnum::Anyone->value,
            'old_permission' => 1,
            'new_permission' => FieldPermissionEnum::ReadOnly->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_role'       => 'registered_perm',
            'new_role'       => SystemRoleEnum::Anyone->value,
            'old_permission' => 2,
            'new_permission' => FieldPermissionEnum::ReadAndWrite->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_role'       => 'author_perm',
            'new_role'       => SystemRoleEnum::Author->value,
            'old_permission' => 1,
            'new_permission' => FieldPermissionEnum::ReadOnly->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_role'       => 'author_perm',
            'new_role'       => SystemRoleEnum::Author->value,
            'old_permission' => 2,
            'new_permission' => FieldPermissionEnum::ReadAndWrite->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_role'       => 'responsible_perm',
            'new_role'       => SystemRoleEnum::Responsible->value,
            'old_permission' => 1,
            'new_permission' => FieldPermissionEnum::ReadOnly->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_role'       => 'responsible_perm',
            'new_role'       => SystemRoleEnum::Responsible->value,
            'old_permission' => 2,
            'new_permission' => FieldPermissionEnum::ReadAndWrite->value,
        ]);

        $this->progressAdvance(count($results));
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_field_perms' table.
     */
    private function migrateFieldPerms(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_field_perms') ?: 1;

        $this->progressStart('field_group_permissions', $count);

        $sql =
            'INSERT INTO field_group_permissions (field_id, group_id, permission) '.
            'SELECT field_id, group_id, :new_permission '.
            'FROM (SELECT field_id, group_id, MAX(perms) as perms FROM tbl_field_perms WHERE perms <> 0 GROUP BY field_id, group_id) t '.
            'WHERE perms = :old_permission '.
            'ORDER BY field_id, group_id';

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_permission' => 1,
            'new_permission' => FieldPermissionEnum::ReadOnly->value,
        ]);

        $this->entityManager->getConnection()->executeStatement($sql, [
            'old_permission' => 2,
            'new_permission' => FieldPermissionEnum::ReadAndWrite->value,
        ]);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_list_values' table.
     */
    private function migrateListValues(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_list_values') ?: 1;

        $this->progressStart('list_items', $count);

        $sql =
            'INSERT INTO list_items (field_id, value, text) '.
            'SELECT field_id, int_value, str_value '.
            'FROM tbl_list_values ORDER BY field_id, int_value';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_float_values' table.
     */
    private function migrateFloatValues(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE decimal_values DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_float_values') ?: 1;

        $this->progressStart('decimal_values', $count);

        $sql =
            'INSERT INTO decimal_values (id, value) '.
            'SELECT value_id, float_value '.
            'FROM tbl_float_values ORDER BY value_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE decimal_values ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_string_values' table.
     */
    private function migrateStringValues(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE string_values DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_string_values') ?: 1;

        $this->progressStart('string_values', $count);

        $sql =
            'INSERT INTO string_values (id, hash, value) '.
            'SELECT value_id, value_token, string_value '.
            'FROM tbl_string_values ORDER BY value_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE string_values ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_text_values' table.
     */
    private function migrateTextValues(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE text_values DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_text_values') ?: 1;

        $this->progressStart('text_values', $count);

        $sql =
            'INSERT INTO text_values (id, hash, value) '.
            'SELECT value_id, value_token, text_value '.
            'FROM tbl_text_values ORDER BY value_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE text_values ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_records' table.
     */
    private function migrateRecords(): void
    {
        // Migrate records.
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE issues DISABLE KEYS');

        $sql =
            'INSERT INTO issues (id, subject, state_id, author_id, responsible_id, created_at, changed_at, closed_at, resumes_at) '.
            'SELECT record_id, subject, state_id, creator_id, responsible_id, creation_time, change_time, closure_time, postpone_time '.
            'FROM tbl_records ORDER BY record_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE issues ENABLE KEYS');

        // Update cloned records with the origins.
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_events WHERE event_type = 10');

        $this->progressStart('issues', count($results));

        foreach ($results as $row) {
            $this->entityManager->getConnection()->executeStatement('UPDATE issues SET origin_id = :origin WHERE id = :id', [
                'id'     => $row['record_id'],
                'origin' => $row['event_param'],
            ]);

            $this->progressAdvance();
        }

        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_reads' table.
     */
    private function migrateReads(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_reads') ?: 1;

        $this->progressStart('last_reads', $count);

        $sql =
            'INSERT INTO last_reads (issue_id, user_id, read_at) '.
            'SELECT record_id, account_id, read_time '.
            'FROM tbl_reads ORDER BY read_time, record_id, account_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_record_subscribes' table.
     */
    private function migrateRecordSubscribes(): void
    {
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_record_subscribes') ?: 1;

        $this->progressStart('watchers', $count);

        $sql =
            'INSERT INTO watchers (issue_id, user_id) '.
            'SELECT record_id, account_id '.
            'FROM tbl_record_subscribes ORDER BY record_id, account_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_events' table.
     */
    private function migrateEvents(): void
    {
        // Migrate events one by one.
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE events DISABLE KEYS');
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_events ORDER BY event_id');

        $this->progressStart('events', count($results));

        $previous = null;

        foreach ($results as $row) {
            $type      = null;
            $parameter = null;

            $sqlUser       = 'SELECT fullname FROM users WHERE id = :id';
            $sqlState      = 'SELECT name FROM states WHERE id = :id';
            $sqlIssue      = 'SELECT prefix FROM templates WHERE id in (SELECT template_id FROM states WHERE id = (SELECT state_id FROM issues WHERE id = :id))';
            $sqlFile       = 'SELECT file_name FROM files WHERE id = :id';
            $sqlDependency = 'SELECT is_dependency FROM tbl_children WHERE parent_id = :parent AND child_id = :child';

            switch ($row['event_type']) {
                case 1:
                    // EVENT_RECORD_CREATED
                    $type      = EventTypeEnum::IssueCreated;
                    $parameter = $this->entityManager->getConnection()->fetchOne($sqlState, ['id' => $row['event_param']]);

                    break;

                case 2:
                    // EVENT_RECORD_ASSIGNED
                    $type = $row['record_id'] === $previous['record_id'] && abs($row['event_time'] - $previous['event_time']) > 1
                        ? EventTypeEnum::IssueReassigned
                        : EventTypeEnum::IssueAssigned;
                    $parameter = $this->entityManager->getConnection()->fetchOne($sqlUser, ['id' => $row['event_param']]);

                    break;

                case 3:
                    // EVENT_RECORD_MODIFIED
                    $type = EventTypeEnum::IssueEdited;

                    break;

                case 4:
                    // EVENT_RECORD_STATE_CHANGED
                    $state = $this->entityManager->getConnection()->fetchAssociative('SELECT * FROM states WHERE id = :id', ['id' => $row['event_param']]);

                    $type      = 'final' === $state['type'] ? EventTypeEnum::IssueClosed : EventTypeEnum::StateChanged;
                    $parameter = $this->entityManager->getConnection()->fetchOne($sqlState, ['id' => $row['event_param']]);

                    break;

                case 5:
                    // EVENT_RECORD_POSTPONED
                    $type      = EventTypeEnum::IssueSuspended;
                    $parameter = $row['event_param'];

                    break;

                case 6:
                    // EVENT_RECORD_RESUMED
                    $type = EventTypeEnum::IssueResumed;

                    break;

                case 7:
                    // EVENT_COMMENT_ADDED
                    $type = EventTypeEnum::PublicComment;

                    break;

                case 8:
                    // EVENT_FILE_ATTACHED
                    $type      = EventTypeEnum::FileAttached;
                    $parameter = $this->entityManager->getConnection()->fetchOne($sqlFile, ['id' => $row['event_param']]);

                    break;

                case 9:
                    // EVENT_FILE_REMOVED
                    $type      = EventTypeEnum::FileDeleted;
                    $parameter = $this->entityManager->getConnection()->fetchOne($sqlFile, ['id' => $row['event_param']]);

                    break;

                case 11:
                    // EVENT_SUBRECORD_ADDED
                    $prefix       = $this->entityManager->getConnection()->fetchOne($sqlIssue, ['id' => $row['event_param']]);
                    $idDependency = $this->entityManager->getConnection()->fetchOne($sqlDependency, ['parent' => $row['record_id'], 'child' => $row['event_param']]);

                    if (false === $idDependency) {
                        $idDependency = true;
                    }

                    $type      = $idDependency ? EventTypeEnum::DependencyAdded : EventTypeEnum::RelatedIssueAdded;
                    $parameter = sprintf('%s-%03d', $prefix, $row['event_param']);

                    break;

                case 12:
                    // EVENT_SUBRECORD_REMOVED
                    $prefix       = $this->entityManager->getConnection()->fetchOne($sqlIssue, ['id' => $row['event_param']]);
                    $idDependency = $this->entityManager->getConnection()->fetchOne($sqlDependency, ['parent' => $row['record_id'], 'child' => $row['event_param']]);

                    if (false === $idDependency) {
                        $idDependency = true;
                    }

                    $type      = $idDependency ? EventTypeEnum::DependencyRemoved : EventTypeEnum::RelatedIssueRemoved;
                    $parameter = sprintf('%s-%03d', $prefix, $row['event_param']);

                    break;

                case 13:
                    // EVENT_CONFIDENTIAL_COMMENT
                    $type = EventTypeEnum::PrivateComment;

                    break;

                case 14:
                    // EVENT_RECORD_REOPENED
                    $type      = EventTypeEnum::IssueReopened;
                    $parameter = $this->entityManager->getConnection()->fetchOne($sqlState, ['id' => $row['event_param']]);

                    break;
            }

            if (null !== $type) {
                $sql =
                    'INSERT INTO events (id, issue_id, user_id, type, created_at, parameter) '.
                    'VALUES (:id, :issue_id, :user_id, :type, :created_at, :parameter)';

                $this->entityManager->getConnection()->executeStatement($sql, [
                    'id'         => $row['event_id'],
                    'issue_id'   => $row['record_id'],
                    'user_id'    => $row['originator_id'],
                    'type'       => $type->value,
                    'created_at' => $row['event_time'],
                    'parameter'  => $parameter,
                ]);
            }

            $previous = $row;

            $this->progressAdvance();
        }

        // Fill in new 'transitions' table.
        $sql =
            'INSERT INTO transitions (event_id, state_id) '.
            'SELECT event_id, event_param '.
            'FROM tbl_events WHERE event_type IN (1, 4, 14) ORDER BY event_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE events ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_field_values' table.
     */
    private function migrateFieldValues(): void
    {
        // Migrate field values.
        $sql =
            'INSERT INTO field_values (transition_id, field_id, value) '.
            'SELECT transitions.id, tbl_field_values.field_id, tbl_field_values.value_id '.
            'FROM tbl_field_values '.
            'JOIN transitions ON transitions.event_id = tbl_field_values.event_id '.
            'ORDER BY id, field_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        // Build a map of existing list items.
        $map     = [];
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM list_items ORDER BY field_id, value');

        foreach ($results as $row) {
            $map[$row['field_id']][$row['value']] = $row['id'];
        }

        // Update "List" field values with items' primary key.
        $sql =
            'SELECT field_values.* '.
            'FROM field_values '.
            'JOIN fields ON fields.id = field_values.field_id '.
            'WHERE fields.type = \'list\' AND field_values.value IS NOT NULL '.
            'ORDER BY id';

        $results = $this->entityManager->getConnection()->fetchAllAssociative($sql);

        $this->progressStart('field_values', count($results));

        foreach ($results as $row) {
            $sql = 'UPDATE field_values SET value = :value WHERE id = :id';

            $this->entityManager->getConnection()->executeStatement($sql, [
                'id'    => $row['id'],
                'value' => $map[$row['field_id']][$row['value']],
            ]);

            $this->progressAdvance();
        }

        $this->progressFinish();
    }

    /**
     * Migrates data from the 'tbl_changes' table.
     */
    private function migrateChanges(): void
    {
        // Migrate changes.
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE changes DISABLE KEYS');

        $sql =
            'INSERT INTO changes (id, event_id, field_id, old_value, new_value) '.
            'SELECT change_id, event_id, field_id, old_value_id, new_value_id '.
            'FROM tbl_changes ORDER BY change_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        // Build a map of existing list items.
        $map     = [];
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM list_items ORDER BY field_id, value');

        foreach ($results as $row) {
            $map[$row['field_id']][$row['value']] = $row['id'];
        }

        // Update "List" changes with items' primary key.
        $sql =
            'SELECT changes.* '.
            'FROM changes '.
            'JOIN fields ON fields.id = changes.field_id '.
            'WHERE fields.type = \'list\' '.
            'ORDER BY id';

        $results = $this->entityManager->getConnection()->fetchAllAssociative($sql);

        $this->progressStart('changes', count($results));

        foreach ($results as $row) {
            if (null !== $row['old_value']) {
                $sql = 'UPDATE changes SET old_value = :value WHERE id = :id';

                $this->entityManager->getConnection()->executeStatement($sql, [
                    'id'    => $row['id'],
                    'value' => $map[$row['field_id']][$row['old_value']],
                ]);
            }

            if (null !== $row['new_value']) {
                $sql = 'UPDATE changes SET new_value = :value WHERE id = :id';

                $this->entityManager->getConnection()->executeStatement($sql, [
                    'id'    => $row['id'],
                    'value' => $map[$row['field_id']][$row['new_value']],
                ]);
            }

            $this->progressAdvance();
        }

        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE changes ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_comments' table.
     */
    private function migrateComments(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE comments DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_comments') ?: 1;

        $this->progressStart('comments', $count);

        $sql =
            'INSERT INTO comments (id, event_id, body, private) '.
            'SELECT comment_id, event_id, comment_body, is_confidential '.
            'FROM tbl_comments ORDER BY comment_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE comments ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_attachments' table.
     */
    private function migrateAttachments(): void
    {
        $this->entityManager->getConnection()->executeStatement('ALTER TABLE files DISABLE KEYS');
        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM tbl_attachments') ?: 1;

        $this->progressStart('files', $count);

        $sql =
            'INSERT INTO files (id, event_id, uid, file_name, file_size, mime_type, removed_at) '.
            'SELECT attachment_id, tbl_attachments.event_id, attachment_id AS uid, attachment_name, attachment_size, attachment_type, event_time '.
            'FROM tbl_attachments '.
            'LEFT JOIN tbl_events ON tbl_events.event_type = 9 AND tbl_events.event_param = tbl_attachments.attachment_id '.
            'ORDER BY attachment_id';

        $this->entityManager->getConnection()->executeStatement($sql);

        $this->progressAdvance($count);
        $this->progressFinish();

        $this->entityManager->getConnection()->executeStatement('ALTER TABLE files ENABLE KEYS');
    }

    /**
     * Migrates data from the 'tbl_children' table.
     */
    private function migrateChildren(): void
    {
        // Migrate dependencies one by one.
        $results = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM tbl_children ORDER BY parent_id, child_id');

        $this->progressStart('dependencies', count($results));

        foreach ($results as $row) {
            $sql =
                'SELECT event_id '.
                'FROM tbl_events '.
                'WHERE event_type = 11 AND record_id = :parent_id AND event_param = :child_id '.
                'ORDER BY event_id DESC';

            $event = $this->entityManager->getConnection()->fetchAssociative($sql, [
                'parent_id' => $row['parent_id'],
                'child_id'  => $row['child_id'],
            ]);

            $sql = $row['is_dependency']
                ? 'INSERT INTO dependencies (event_id, issue_id) VALUES (:event_id, :issue_id)'
                : 'INSERT INTO related_issues (event_id, issue_id) VALUES (:event_id, :issue_id)';

            $this->entityManager->getConnection()->executeStatement($sql, [
                'event_id' => $event['event_id'],
                'issue_id' => $row['child_id'],
            ]);

            $this->progressAdvance();
        }

        $this->progressFinish();
    }
}
