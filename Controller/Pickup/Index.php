<?php
/**
 * Created by PhpStorm.
 * User: awstreams
 * Date: 2/15/18
 * Time: 2:03 PM
 */

namespace DPDFrance\Shipping\Controller\Pickup;


use DPDFrance\Shipping\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{

    protected $helper;
    protected $checkoutSession;
    protected $assetRepo;
    protected $storeManager;
    public function __construct(
        Context $context,
        Data $helper,
        Session $checkoutSession,
        AssetRepository $assetRepo,
        StoreManagerInterface $storeManager
    )
    {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->assetRepo = $assetRepo;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $store = $this->storeManager->getStore();
        $address = $this->getRequest()->getParam('address');
        $address = mb_convert_encoding(urldecode($address),'UTF-8');
        $address = $this->stripAccents($address);

        $zipcode = $this->getRequest()->getParam('zipcode');
        $zipcode = trim(urldecode($zipcode));
        $zipcode = mb_convert_encoding($zipcode,'UTF-8');

        $city = $this->getRequest()->getParam('city');
        $city = mb_convert_encoding(urldecode($city),'UTF-8');
        $city = $this->stripAccents($city);
        $pageHtml = '';
        if (empty($zipcode))
            $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'. __('The field Postal Code is mandatory!') . '</li></ul></li></ul>';
        else
        {
            if (empty($city))
                $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'. __('The field City is mandatory!') . '</li></ul></li></ul>';
            else
            {
                $serviceurl = $this->helper->getConfigValue('carriers/dpdfrrelais/serviceurl');
                $firmid = $this->helper->getConfigValue('carriers/dpdfrrelais/indentifier');
                $key = $this->helper->getConfigValue('carriers/dpdfrrelais/key');

                // Paramètres d'appel au WS MyPudo
                $variables = array(
                    'carrier'           => $firmid,
                    'key'               => $key,
                    'address'           => $address,
                    'zipCode'           => $zipcode,
                    'city'              => $city,
                    'countrycode'       => 'FR',
                    'requestID'         => '1234',
                    'request_id'        => '1234',
                    'date_from'         => date('d/m/Y'),
                    'max_pudo_number'   => '',
                    'max_distance_search'=> '',
                    'weight'            => '',
                    'category'          => '',
                    'holiday_tolerant'  => ''
                );

                // Message d'erreur si PHP_SOAP manquant
                if (!extension_loaded('soap'))
                    $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'.__('ATTENTION! L\'extension PHP SOAP n\'est pas activée sur ce serveur. Vous devez l\'activer pour utiliser le module DPD Relais.').'</li></ul></li></ul>';
                // Appel WS
                try
                {
                    ini_set("default_socket_timeout", 3);
                    $soappudo = new \SoapClient($serviceurl,array('connection_timeout' => 3,'cache_wsdl' => WSDL_CACHE_NONE, 'exceptions' => true));
                    $GetPudoList = $soappudo->getPudoList($variables); // appel SOAP a l'applicatif GetPudoList
                }
                catch (\Exception $e)
                {
                    $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'.__('An error ocurred while fetching the DPD Pickup points. Please try again').'</li></ul></li></ul>';
                }
                $doc_xml = new \SimpleXMLElement($GetPudoList->GetPudoListResult->any);  // parsage XML de la réponse SOAP

                $quality = (int)$doc_xml->attributes()->quality; // indice de qualité de la réponse SOAP

                if ($doc_xml->xpath('ERROR')) // si le webservice répond un code erreur, afficher un message d'indisponibilité
                    $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'.__('An error ocurred while fetching the DPD Pickup points. Please try again').'</li></ul></li></ul>';
                else
                {
                    if ((int)$quality == 0)// Si la qualité de la réponse est 0, "merci d'indiquer une autre adresse"
                        $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'.__('There are no DPD Pickup points for the selected adress. Please modify it.').'</li></ul></li></ul>';
                    else
                    {
                        $allpudoitems = $doc_xml->xpath('PUDO_ITEMS'); // acceder a la balise pudo_items

                        foreach ($allpudoitems as $singlepudoitem) // eclatement des données contenues dans pudo_items
                        {
                            $result = $singlepudoitem->xpath('PUDO_ITEM');
                            $i=0;
                            foreach($result as $result2)
                            {
                                /* DPD FR 31.08.2017 -> Filtrage points relais zones iles / montagne */
                                $cp_client = $this->checkoutSession->getQuote()->getShippingAddress()->getPostcode();
                                $zone_iles=array('17111', '17123', '17190', '17310', '17370', '17410', '17480', '17550', '17580', '17590', '17630', '17650', '17670', '17740', '17840', '17880', '17940', '22870', '29242', '29253', '29259', '29980', '29990', '56360', '56590', '56780', '56840', '85350');
                                $zone_montagne=array('04120', '04130', '04140', '04160', '04170', '04200', '04240', '04260', '04300', '04310', '04330', '04360', '04370', '04400', '04510', '04530', '04600', '04700', '04850', '05100', '05110', '05120', '05130', '05150', '05160', '05170', '05200', '05220', '05240', '05250', '05260', '05290', '05300', '05310', '05320', '05330', '05340', '05350', '05400', '05460', '05470', '05500', '05560', '05600', '05700', '05800', '06140', '06380', '06390', '06410', '06420', '06430', '06450', '06470', '06530', '06540', '06620', '06710', '06750', '06910', '09110', '09140', '09300', '09460', '25120', '25140', '25240', '25370', '25450', '25500', '25650', '30570', '31110', '38112', '38114', '38142', '38190', '38250', '38350', '38380', '38410', '38580', '38660', '38700', '38750', '38860', '38880', '39220', '39310', '39400', '63113', '63210', '63240', '63610', '63660', '63690', '63840', '63850', '64440', '64490', '64560', '64570', '65110', '65120', '65170', '65200', '65240', '65400', '65510', '65710', '66210', '66760', '66800', '68140', '68610', '68650', '73110', '73120', '73130', '73140', '73150', '73160', '73170', '73190', '73210', '73220', '73230', '73250', '73260', '73270', '73300', '73320', '73340', '73350', '73390', '73400', '73440', '73450', '73460', '73470', '73500', '73530', '73550', '73590', '73600', '73620', '73630', '73640', '73710', '73720', '73870', '74110', '74120', '74170', '74220', '74230', '74260', '74310', '74340', '74350', '74360', '74390', '74400', '74420', '74430', '74440', '74450', '74470', '74480', '74660', '74740', '74920', '83111', '83440', '83530', '83560', '83630', '83690', '83830', '83840', '84390', '88310', '88340', '88370', '88400', '90200');
                                // Client dans zone iles -> proposer PR dans zone iles
                                if(in_array($cp_client, $zone_iles)) {
                                    if(!in_array($result2->ZIPCODE, $zone_iles)||(substr($result2->ZIPCODE, 0, 2)!='20'))
                                        continue;
                                }else{
                                    // Client hors zone iles -> exclure PR de la zone iles
                                    if(in_array($result2->ZIPCODE, $zone_iles)||(substr($result2->ZIPCODE, 0, 2)=='20'))
                                        continue;
                                }
                                // Client dans zone montagne -> proposer PR dans zone montagne
                                if(in_array($cp_client, $zone_montagne)) {
                                    if(!in_array($result2->ZIPCODE, $zone_montagne))
                                        continue;
                                }else{
                                    // Client hors zone iles -> exclure PR de la zone iles
                                    if (in_array($result2->ZIPCODE, $zone_montagne))
                                        continue;
                                }
                                /* Fin 31.08.2017 */

                                $offset = $i;
                                $LATITUDE = (float)str_replace(",",".",(string)$result2->LATITUDE);
                                $LONGITUDE = (float)str_replace(",",".",(string)$result2->LONGITUDE);

                                $html = '
                                <div style="width:100% !important; clear:both;">
                                    <span class="dpdfrrelais_logo" style="float:left;"><img src="'. $this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/pointrelais.png', ['_secure'=>true]) .'" alt="-"/></span>
                                    <span class="s1" style="width:50%; float:left;"><strong id="titlerelaydetail'.$offset.'" data-popup-title="'.$this->stripAccents($result2->NAME).'">'.$this->stripAccents($result2->NAME).'</strong><br/>'.$this->stripAccents($result2->ADDRESS1).' <br/> '.$result2->ZIPCODE.' '.$this->stripAccents($result2->CITY).'</span>
                                    <span class="s2" style="width:20%; float:left;">'.sprintf("%01.2f", (int)$result2->DISTANCE/1000).' km  </span>
                                    <span class="s3" style="width:30%; float:left;"><a href="#" id="dpd_more_details" data-id="relaydetail'.$offset.'" data-map-canvas="map_canvas'.$offset.'" data-latitude="'.$LATITUDE.'" data-longtitude="'.$LONGITUDE.'" data-media-url="'.$store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'">'.__('More details').'</a></span>
                                    <input type="radio" id="relay-point'.$offset.'" name="relay-point" class="dpdfrrelais_radio" value="'.$this->stripAccents($result2->ADDRESS1).'  '.$this->stripAccents($result2->ADDRESS2).'|||'.$this->stripAccents($result2->NAME).'  '.(string)$result2->PUDO_ID.'|||'.$result2->ZIPCODE.'|||'.$this->stripAccents($result2->CITY).'">
                                    <label class="dpdfrrelais_button_ici" for="relay-point'.$offset.'"><span><span></span></span><b>ICI</b></label>
                                </div>
                                ';

                                $days=array(1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday',7=>'sunday');
                                $point=array();
                                $item=(array)$result2;

                                if(count($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM)>0)foreach($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM as $k=>$oh_item)
                                {
                                    $oh_item=(array)$oh_item;
                                    $point[$days[$oh_item['DAY_ID']]][]=$oh_item['START_TM'].' - '.$oh_item['END_TM'];
                                }

                                if(empty($point['monday'])){$h1 = __ ('Closed');}
                                else{if(empty($point['monday'][1])){$h1 = $point['monday'][0];}
                                else{$h1 = $point['monday'][0].' & '.$point['monday'][1];}}

                                if(empty($point['tuesday'])){$h2 = __ ('Closed');}
                                else{if(empty($point['tuesday'][1])){$h2 = $point['tuesday'][0];}
                                else{$h2 = $point['tuesday'][0].' & '.$point['tuesday'][1];}}

                                if(empty($point['wednesday'])){$h3 = __ ('Closed');}
                                else{if(empty($point['wednesday'][1])){$h3 = $point['wednesday'][0];}
                                else{$h3 = $point['wednesday'][0].' & '.$point['wednesday'][1];}}

                                if(empty($point['thursday'])){$h4 = __ ('Closed');}
                                else{if(empty($point['thursday'][1])){$h4 = $point['thursday'][0];}
                                else{$h4 = $point['thursday'][0].' & '.$point['thursday'][1];}}

                                if(empty($point['friday'])){$h5 = __ ('Closed');}
                                else{if(empty($point['friday'][1])){$h5 = $point['friday'][0];}
                                else{$h5 = $point['friday'][0].' & '.$point['friday'][1];}}

                                if(empty($point['saturday'])){$h6 = __ ('Closed');}
                                else{if(empty($point['saturday'][1])){$h6 = $point['saturday'][0];}
                                else{$h6 = $point['saturday'][0].' & '.$point['saturday'][1];}}

                                if(empty($point['sunday'])){$h7 = __ ('Closed');}
                                else{if(empty($point['sunday'][1])){$h7 = $point['sunday'][0];}
                                else{$h7 = $point['sunday'][0].' & '.$point['sunday'][1];}}

                                $html .= '<div id="relaydetail'.$offset.'" style="display:none;">
                                            <div class="dpdfrrelaisboxcarto" id="map_canvas'.$offset.'" style="width:100%;height:400px"></div>
                                            <div id="dpdfrrelaisboxbottom" class="dpdfrrelaisboxbottom">
                                                <div id="dpdfrrelaisboxadresse" class="dpdfrrelaisboxadresse">
                                                    <div class="dpdfrrelaisboxadresseheader"><img src="'.$this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/pointrelais.png', ['_secure'=>true]).'" alt="-" width="32" height="32"/><br/>'.__('Your DPD Pickup point').'</div>
                                                    <strong>'.$result2->NAME.'</strong></br>
                                                    '.$result2->ADDRESS1.'</br>';
                                if (!empty($result2->ADDRESS2))
                                    $html .= $result2->ADDRESS2.'</br>';
                                $html .= $result2->ZIPCODE.'  '.$result2->CITY.'<br/>';
                                if (!empty($result2->LOCAL_HINT))
                                    $html .= '<p>'.__('info').'  :  '.$result2->LOCAL_HINT.'</p>';
                                $html .= '</div>';

                                $html .= '<div class="dpdfrrelaisboxhoraires">
                                            <div class="dpdfrrelaisboxhorairesheader"><img src="'.$this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/horaires.png', ['_secure'=>true]).'" alt="-" width="32" height="32"/><br/>'.__('Opening hours').'</div>
                                            <p><span>'.__('Monday').' : </span>'.$h1.'</p>
                                            <p><span>'.__('Tuesday').' : </span>'.$h2.'</p>
                                            <p><span>'.__('Wednesday').' : </span>'.$h3.'</p>
                                            <p><span>'.__('Thursday').' : </span>'.$h4.'</p>
                                            <p><span>'.__('Friday').' : </span>'.$h5.'</p>
                                            <p><span>'.__('Saturday').' : </span>'.$h6.'</p>
                                            <p><span>'.__('Sunday').' : </span>'.$h7.'</p>
                                        </div>';

                                $html .= '<div class="dpdfrrelaisboxinfos">
                                            <div class="dpdfrrelaisboxinfosheader"><img src="'.$this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/info.png', ['_secure'=>true]).'" alt="-" width="32" height="32"/><br/>'.__('More info').'</div>
                                            <div><h5>'.__('Distance in KM').'  :  </h5><strong>'.sprintf("%01.2f", $result2->DISTANCE/1000).' km </strong></div>
                                            <div><h5>'.__('DPD Pickup ID#').'  :  </h5><strong>'.(string)$result2->PUDO_ID.'</strong></div>';
                                if (count($result2->HOLIDAY_ITEMS->HOLIDAY_ITEM) > 0)
                                {
                                    foreach ($result2->HOLIDAY_ITEMS->HOLIDAY_ITEM as $holiday_item)
                                    {
                                        $holiday_item = (array)$holiday_item;
                                        $html .= '<div><img id="dpdfrrelaisboxinfoswarning" src="'.$this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/warning.png', ['_secure'=>true]).'" alt="-" width="16" height="16"/> <h4>'.__('Closing period').'  : </h4> '.$holiday_item['START_DTM'].' - '.$holiday_item['END_DTM'].'</div>';
                                    }
                                }

                                $html .= '</div></div></div>'; // dpdfrrelaisboxbottom et relaydetail
                                $pageHtml .= $html;

                                $i++;
                                $hd1 = $hd2 = $hd3 = $hd4 = $hd5 = $hd6 = $hd7 = $h1 = $h2 = $h3 = $h4 = $h5 = $h6 = $h7 = null;
                                if($i == 5) // Nombre de points relais à afficher - max 10
                                    break;
                            }
                            if(!isset($html))
                                $pageHtml .= '<ul class="messages"><li class="warnmsg"><ul><li>'.__('There are no DPD Pickup points for the selected adress. Please modify it.').'</li></ul></li></ul>';
                        }
                    }
                }
            }
        }
        die( $pageHtml );
    }

    public static function stripAccents($str)
    {
        $str = preg_replace('/[\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u','A', $str);
        $str = preg_replace('/[\x{0105}\x{0104}\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]/u','a', $str);
        $str = preg_replace('/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u','C', $str);
        $str = preg_replace('/[\x{00E7}\x{0107}\x{0109}\x{010B}\x{010D}}]/u','c', $str);
        $str = preg_replace('/[\x{010E}\x{0110}]/u','D', $str);
        $str = preg_replace('/[\x{010F}\x{0111}]/u','d', $str);
        $str = preg_replace('/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u','E', $str);
        $str = preg_replace('/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u','e', $str);
        $str = preg_replace('/[\x{00CC}\x{00CD}\x{00CE}\x{00CF}\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u','I', $str);
        $str = preg_replace('/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u','i', $str);
        $str = preg_replace('/[\x{0142}\x{0141}\x{013E}\x{013A}]/u','l', $str);
        $str = preg_replace('/[\x{00F1}\x{0148}]/u','n', $str);
        $str = preg_replace('/[\x{00D2}\x{00D3}\x{00D4}\x{00D5}\x{00D6}\x{00D8}]/u','O', $str);
        $str = preg_replace('/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}]/u','o', $str);
        $str = preg_replace('/[\x{0159}\x{0155}]/u','r', $str);
        $str = preg_replace('/[\x{015B}\x{015A}\x{0161}]/u','s', $str);
        $str = preg_replace('/[\x{00DF}]/u','ss', $str);
        $str = preg_replace('/[\x{0165}]/u','t', $str);
        $str = preg_replace('/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{016E}\x{0170}\x{0172}]/u','U', $str);
        $str = preg_replace('/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{016F}\x{0171}\x{0173}]/u','u', $str);
        $str = preg_replace('/[\x{00FD}\x{00FF}]/u','y', $str);
        $str = preg_replace('/[\x{017C}\x{017A}\x{017B}\x{0179}\x{017E}]/u','z', $str);
        $str = preg_replace('/[\x{00C6}]/u','AE', $str);
        $str = preg_replace('/[\x{00E6}]/u','ae', $str);
        $str = preg_replace('/[\x{0152}]/u','OE', $str);
        $str = preg_replace('/[\x{0153}]/u','oe', $str);
        $str = preg_replace('/[\x{0022}\x{0025}\x{0026}\x{0027}\x{00A1}\x{00A2}\x{00A3}\x{00A4}\x{00A5}\x{00A6}\x{00A7}\x{00A8}\x{00AA}\x{00AB}\x{00AC}\x{00AD}\x{00AE}\x{00AF}\x{00B0}\x{00B1}\x{00B2}\x{00B3}\x{00B4}\x{00B5}\x{00B6}\x{00B7}\x{00B8}\x{00BA}\x{00BB}\x{00BC}\x{00BD}\x{00BE}\x{00BF}]/u',' ', $str);
        return $str;
    }
}