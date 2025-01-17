<?php

namespace AzurePimcoreBundle\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Cache;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Cache\Symfony\CacheClearer;
//use Pimcore\Config;
use Pimcore\Db\Connection;
use Pimcore\Event\SystemEvents;
use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Glossary;
use Pimcore\Model\Metadata;
use Pimcore\Model\Property;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Tag;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use AzurePimcoreBundle\Lib\Config;

class AzureSettingController extends FrontendController {

    /**
     * @Route("/admin/settings/get-azure", name="get_azure")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAzureAction(Request $request)
    {
//        $this->checkPermission('azure_settings');

        $values = Config::getAzureConfig();
        $valueArray = $values->toArray();
        $optionsString = array();
        if ($valueArray['azureOptions']) {
            foreach ($valueArray['azureOptions'] as $key => $value) {
                $tmpStr = '--'.$key;
                if ($value !== null && $value !== '') {
                    $tmpStr .= ' '.$value;
                }
                $optionsString[] = $tmpStr;
            }
        }
        $valueArray['azureOptions'] = implode("\n", $optionsString);

        $response = array(
            'values' => $valueArray
        );

        return $this->json($response);
    }
    
    /**
     * @Route("/admin/settings/set-azure", name="set_azure")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function setAzureAction(Request $request)
    {
//        $this->checkPermission('azure_settings');

        $values = $this->decodeJson($request->get('data'));

        if ($values['azureOptions']) {
            $optionArray = array();
            $lines = explode("\n", $values['azureOptions']);
            foreach ($lines as $line) {
                $parts = explode(' ', substr($line, 2));
                $key = trim($parts[0]);
                if ($key) {
                    $value = trim($parts[1]);
                    $optionArray[$key] = $value;
                }
            }
            $values['azureOptions'] = $optionArray;
        }

        $configFile = \Pimcore\Config::locateConfigFile('azure.php');       
        File::putPhpFile($configFile, to_php_data_file_format($values));

        return $this->json(array('success' => true));
    }
    
    /**
     * Decodes a JSON string into an array/object
     *
     * @param mixed $json       The data to be decoded
     * @param bool $associative Whether to decode into associative array or object
     * @param array $context    Context to pass to serializer when using serializer component
     * @param bool $useAdminSerializer
     *
     * @return array|\stdClass
     */
    protected function decodeJson($json, $associative = true, array $context = array(), $useAdminSerializer = true)
    {
        /** @var SerializerInterface|DecoderInterface $serializer */
        
        $serializer = null;

        if ($useAdminSerializer) {
            $serializer = $this->container->get('pimcore_admin.serializer');
        } else {
            $serializer = $this->container->get('serializer');
        }

        if ($associative) {
            $context['json_decode_associative'] = true;
        }

        return $serializer->decode($json, 'json', $context);
    }
    
}
