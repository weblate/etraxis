<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Command;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\Enums\ThemeEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Exports enums to frontend as JavaScript constants.
 */
#[AsCommand(
    name: 'etraxis:export-enums',
    description: 'Export enums to frontend as JavaScript constants',
    hidden: true
)]
class ExportEnumsCommand extends Command
{
    /**
     * @var \BackedEnum[] list of enums to export
     */
    protected array $enums = [
        AccountProviderEnum::class,
        ThemeEnum::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        file_exists('assets/enums') || mkdir('assets/enums');

        foreach ($this->enums as $enum) {
            $javascript = [
                '// This file is autogenerated using '.$this->getName(),
                'export default {',
            ];

            foreach ($enum::cases() as $case) {
                $javascript[] = sprintf('    "%s": "%s",', $case->value, $case->name);
            }

            $javascript[] = '};';
            $javascript[] = null;

            $filename = strtolower(str_replace(['App\\Entity\\Enums\\', 'Enum'], ['', ''], $enum));
            file_put_contents("assets/enums/{$filename}.js", implode("\n", $javascript));
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('Successfully exported.');

        return Command::SUCCESS;
    }
}
