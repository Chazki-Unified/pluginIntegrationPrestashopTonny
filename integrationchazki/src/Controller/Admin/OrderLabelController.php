<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
namespace IntegrationChazki\Controller\Admin;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Tools;
use Db;
// use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\CustomerException;

class OrderLabelController extends FrameworkBundleAdminController
{
    // 'https://us-central1-chazki-link-beta.cloudfunctions.net/fnGetLabelOrder?enterpriseKey=39efd1fd-345f-4353-8ce8-55ed6dacbe0e&trackCode=1264690506277-01'
    public function demoAction()
    {
        $trackCode = $_GET['reference'];
        if (!$trackCode) {
            return new Response('TrackCode not found');
        }

        $enterpriseKey = $this->getEnterpriseKey();
        if (!$enterpriseKey) {
            return new Response('Register enterpriseKey');
        }

        $url = 'https://us-central1-chazki-link.cloudfunctions.net/fnGetLabelOrder?enterpriseKey=' . $enterpriseKey . '&trackCode=' . $trackCode;
        $bodyPdf = new Response(Tools::file_get_contents($url));
        $bodyPdf->headers->set('Content-Type', 'application/pdf');

        return $bodyPdf;
    }

    protected function getEnterpriseKey()
    {
        $sql = 'SELECT `value` FROM ' . _DB_PREFIX_ . 'configuration WHERE UPPER(`name`) = UPPER("' . _DB_PREFIX_ . 'CHAZKI_API_KEY")';

        return '' . Db::getInstance()->getValue($sql);
    }
}
