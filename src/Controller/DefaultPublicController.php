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

namespace App\Controller;

use League\ISO3166\ISO3166;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Default public area controller.
 */
class DefaultPublicController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        return $this->render('settings/index.html.twig');
    }

    #[Route('/timezones', name: 'timezones')]
    public function timezones(): JsonResponse
    {
        $countries    = [];
        $allTimezones = timezone_identifiers_list();

        foreach ($allTimezones as $timezone) {
            $location = timezone_location_get(new \DateTimeZone($timezone));
            $code     = $location['country_code'];

            if ('??' !== $code) {
                $cities = [];
                $data   = (new ISO3166())->alpha2($code);

                $countryTimezones = timezone_identifiers_list(\DateTimeZone::PER_COUNTRY, $code);

                foreach ($countryTimezones as $countryTimezone) {
                    $parts = explode('/', $countryTimezone);

                    $cities[$countryTimezone] = str_replace('_', ' ', end($parts));
                }

                asort($cities);

                $countries[$data['name']] = $cities;
            }
        }

        asort($countries);

        return $this->json($countries);
    }
}
