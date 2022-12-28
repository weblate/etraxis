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

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fakes required frontend files.
 */
#[AsCommand(
    name: 'etraxis:fake-webpack',
    description: 'Fake required frontend files',
    hidden: true
)]
class FakeWebpackCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entrypoints = [];

        $file = fopen('webpack.config.js', 'r');

        while (!feof($file)) {
            $line = trim(fgets($file));

            if (str_starts_with($line, '.addEntry')) {
                $parts = explode('\'', $line, 3);

                if (count($parts) >= 3) {
                    $entrypoints[$parts[1]] = [];
                }
            }
        }

        fclose($file);

        file_exists('public/build') || mkdir('public/build');
        file_put_contents('public/build/entrypoints.json', json_encode(['entrypoints' => $entrypoints]));
        file_put_contents('public/build/manifest.json', '{}');

        $io = new SymfonyStyle($input, $output);
        $io->success('Done.');

        return Command::SUCCESS;
    }
}
