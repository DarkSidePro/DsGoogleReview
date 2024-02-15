<?php

declare(strict_types=1);

namespace DarkSide\DsGoogleReview\Controller;

use DarkSide\DsGoogleReview\Service\DsGoogleReviewService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DsGoogleReviewsController extends FrameworkBundleAdminController
{
    private DsGoogleReviewService $dsGoogleReviewService;

    public function __construct(DsGoogleReviewService $dsGoogleReviewService)
    {
        $this->dsGoogleReviewService = $dsGoogleReviewService;
    }

    public function index(Request $request): Response
    {
        $configurationHandler = $this->get('darkside.dsgooglereviews.form.ds_google_reviews_text_data_configuration');

        $configurationForm = $configurationHandler->getForm();
        $configurationForm->handleRequest($request);

        if ($configurationForm->isSubmitted() && $configurationForm->isValid()) {
            /** You can return array of errors in form handler and they can be displayed to user with flashErrors */
            $errors = $configurationHandler->save($configurationForm->getData());

            if (empty($errors)) {
                $this->dsGoogleReviewService->fetchAndCacheReviews();
                
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('ds_google_reviews_form');
            }

            $this->flashErrors($errors);
        }

        return $this->render('@Modules/demosymfonyformsimple/views/templates/admin/form.html.twig', [
            'form' => $configurationForm->createView(),
        ]);
    }
}
