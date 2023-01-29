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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Default admin area controller.
 */
#[Route('/admin')]
class DefaultAdminController extends AbstractController
{
    #[Route('')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/users', name: 'admin_users')]
    public function users(): Response
    {
        return $this->render('users/index.html.twig');
    }
}
