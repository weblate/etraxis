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

use App\Entity\Project;
use App\Entity\User;
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
        return $this->render('users/index.html.twig', [
            'timezone' => date_default_timezone_get(),
        ]);
    }

    #[Route('/users/{id}', requirements: ['id' => '\d+'])]
    public function viewUser(User $user): Response
    {
        return $this->render('users/view.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/projects', name: 'admin_projects')]
    public function projects(): Response
    {
        return $this->render('projects/index.html.twig');
    }

    #[Route('/projects/{id}', requirements: ['id' => '\d+'])]
    public function viewProject(Project $project): Response
    {
        return $this->render('projects/view.html.twig', [
            'project' => $project,
        ]);
    }
}
