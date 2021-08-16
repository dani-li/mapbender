<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Repository\ApplicationRepository;
use Mapbender\CoreBundle\Entity\Repository\SourceInstanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class ApplicationControllerBase extends Controller
{
    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        return $em;
    }

    /**
     * @param string $id
     * @param Application|null $application
     * @return Layerset
     */
    protected function requireLayerset($id, $application = null)
    {
        if ($application) {
            $layersetCriteria = Criteria::create()->where(Criteria::expr()->eq('id', $id));
            /** @var Layerset|false $layerset */
            $layerset = $application->getLayersets()->matching($layersetCriteria)->first();
        } else {
            $repository = $this->getEntityManager()->getRepository('MapbenderCoreBundle:Layerset');
            $layerset = $repository->find($id);
        }
        if (!$layerset) {
            throw $this->createNotFoundException("No such layerset");
        }
        return $layerset;
    }

    /**
     * @return SourceInstanceRepository
     */
    protected function getSourceInstanceRepository()
    {
        return $this->getDoctrine()->getRepository('\Mapbender\CoreBundle\Entity\SourceInstance');
    }

    /**
     * @return ApplicationRepository
     */
    protected function getDbApplicationRepository()
    {
        return $this->getDoctrine()->getRepository('\Mapbender\CoreBundle\Entity\Application');
    }

    /**
     * @param string $slug
     * @return Application
     */
    protected function requireDbApplication($slug)
    {
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$application) {
            throw $this->createNotFoundException("No such application");
        }
        return $application;
    }
}
