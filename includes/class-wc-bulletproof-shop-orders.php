<?php
if (!defined('ABSPATH')) {
	exit;
}

// Include WooCommerce Bulletproof Shop Orders class.
class Bulletproof_Shop_Orders
{

	/**
	 * Constructor function to initialize the shop orders settings.
	 */
	public function __construct()
	{

		add_action('admin_init',  array($this, 'check_bulletproof_lite_environment'));

		add_action('admin_enqueue_scripts', array($this, 'bulletproof_admin_enqueue_custom_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'bulletproof_frontend_enqueue_scripts'));

		if (BULLETPROOF_CHECKOUT_ADDORDERLISTCOLUMNS) {
			add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'bulletproof_checkout_capture_column_header'), 10, 1);
			add_filter('manage_edit-shop_order_columns', array($this, 'bulletproof_checkout_capture_column_header'), 10, 1);
			add_action('manage_shop_order_posts_custom_column', array($this, 'bulletproof_checkout_capture_column_content_old'), 10, 2);
			add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'bulletproof_checkout_capture_column_content'), 10, 2);
			add_action('wp_ajax_capture_order_payment', array($this, 'bulletproof_capture_order_payment_callback'));
		}

		// Handle order status changes
		add_action('woocommerce_order_status_changed', array($this, 'woo_order_status_change_bpcheckout_lite'), 10, 3);


		// State is not a required field
		add_filter('woocommerce_shipping_fields', array($this, 'bp_unrequire_wc_shipping_state_field'));
		add_filter('woocommerce_billing_fields', array($this, 'bp_unrequire_wc_billing_state_field'));
		add_filter('woocommerce_states', array($this, 'bp_filter_woocommerce_states'), 10, 1);


		if (BULLETPROOF_CHECKOUT_DISABLEJETPACKSSO) {
			// JetPack SSO is a module auto-enabled in some hosting providers like Bluehost which 
			// is in conflict with the Official Woo Mobile App (Mobile App requires JetPack enabled)
			// more info here: https://jetpack.com/support/getting-started-with-jetpack/known-issues/
			function jetpackcom_support_disable_jetpack_sso($modules)
			{
				$found = array_search('sso', $modules, true);
				if (false !== $found) {
					unset($modules[$found]);
				}

				return $modules;
			}
			add_filter('option_jetpack_active_modules', 'jetpackcom_support_disable_jetpack_sso');
		}
	}

	public function bp_filter_woocommerce_states($states)
	{
		foreach ($states as $state_key => $state_value) {
			$states[$state_key]['hidden'] = false;
		}





		// Add all the states missed by Woo 

		$states['AF'] = array(
			'BDS' => __('Badakhshan', 'woocommerce'),
			'BDG' => __('Badghis', 'woocommerce'),
			'BGL' => __('Baghlan', 'woocommerce'),
			'BAL' => __('Balkh', 'woocommerce'),
			'BAM' => __('Bamyan', 'woocommerce'),
			'DAY' => __('Daykundi', 'woocommerce'),
			'FRA' => __('Farah', 'woocommerce'),
			'FYB' => __('Faryab', 'woocommerce'),
			'GHA' => __('Ghazni', 'woocommerce')     // There are some other missed states here
		);
		$states['AT'] = array(
			'1' => __('Burgenland', 'woocommerce'),
			'2' => __('Carinthia', 'woocommerce'),
			'3' => __('Lower Austria', 'woocommerce'),
			'5' => __('Salzburg', 'woocommerce'),
			'6' => __('Styria', 'woocommerce'),
			'7' => __('Tyrol', 'woocommerce'),
			'4' => __('Upper Austria', 'woocommerce'),
			'9' => __('Vienna', 'woocommerce'),
			'8' => __('Vorarlberg', 'woocommerce')
		);
		$states['ET'] = array(
			'SO' => __('Somali Region', 'woocommerce'),
			'AM' => __('Amhara Region', 'woocommerce'),
			'TI' => __('Tigray Region', 'woocommerce'),
			'OR' => __('Oromia Region', 'woocommerce'),
			'AF' => __('Afar Region', 'woocommerce'),
			'HA' => __('Harari Region', 'woocommerce'),
			'DD' => __('Dire Dawa', 'woocommerce'),
			'BE' => __('Benishangul-Gumuz Region', 'woocommerce'),
			'GA' => __('Gambela Region', 'woocommerce'),
			'AA' => __('Addis Ababa', 'woocommerce')
		);
		$states['MT'] = array(
			'33' => __('Mqabba', 'woocommerce'),
			'49' => __('San Gwann', 'woocommerce'),
			'68' => __('Zurrieq', 'woocommerce'),
			'25' => __('Luqa', 'woocommerce'),
			'28' => __('Marsaxlokk', 'woocommerce'),
			'42' => __('Qala', 'woocommerce'),
			'66' => __('Zebbug Malta', 'woocommerce'),
			'63' => __('Xghajra', 'woocommerce'),
			'23' => __('Kirkop', 'woocommerce'),
			'46' => __('Rabat', 'woocommerce'),
			'9' => __('Floriana', 'woocommerce'),
			'65' => __('Zebbug Gozo', 'woocommerce'),
			'57' => __('Swieqi', 'woocommerce'),
			'50' => __('Saint Lawrence', 'woocommerce'),
			'5' => __('Birzebbuga', 'woocommerce'),
			'29' => __('Mdina', 'woocommerce'),
			'54' => __('Santa Venera', 'woocommerce'),
			'22' => __('Kercem', 'woocommerce'),
			'14' => __('Gharb', 'woocommerce'),
			'19' => __('Iklin', 'woocommerce'),
			'53' => __('Santa Lucija', 'woocommerce'),
			'60' => __('Valletta', 'woocommerce'),
			'34' => __('Msida', 'woocommerce'),
			'4' => __('Birkirkara', 'woocommerce'),
			'55' => __('Siggiewi', 'woocommerce'),
			'21' => __('Kalkara', 'woocommerce'),
			'48' => __('St. Julians', 'woocommerce'),
			'45' => __('Victoria', 'woocommerce'),
			'30' => __('Mellieha', 'woocommerce'),
			'59' => __('Tarxien', 'woocommerce'),
			'56' => __('Sliema', 'woocommerce'),
			'18' => __('Hamrun', 'woocommerce'),
			'16' => __('Ghasri', 'woocommerce'),
			'3' => __('Birgu', 'woocommerce'),
			'2' => __('Balzan', 'woocommerce'),
			'31' => __('Mgarr', 'woocommerce'),
			'1' => __('Attard', 'woocommerce'),
			'44' => __('Qrendi', 'woocommerce'),
			'38' => __('Naxxar', 'woocommerce'),
			'12' => __('Gzira', 'woocommerce'),
			'61' => __('Xaghra', 'woocommerce'),
			'39' => __('Paola', 'woocommerce'),
			'52' => __('Sannat', 'woocommerce'),
			'7' => __('Dingli', 'woocommerce'),
			'11' => __('Gudja', 'woocommerce'),
			'43' => __('Qormi', 'woocommerce'),
			'15' => __('Gharghur', 'woocommerce'),
			'62' => __('Xewkija', 'woocommerce'),
			'58' => __('Ta Xbiex', 'woocommerce'),
			'64' => __('Zabbar', 'woocommerce'),
			'17' => __('Ghaxaq', 'woocommerce'),
			'40' => __('Pembroke', 'woocommerce'),
			'24' => __('Lija', 'woocommerce'),
			'41' => __('Pieta', 'woocommerce'),
			'26' => __('Marsa', 'woocommerce'),
			'8' => __('Fgura', 'woocommerce'),
			'13' => __('Ghajnsielem', 'woocommerce'),
			'35' => __('Mtarfa', 'woocommerce'),
			'36' => __('Munxar', 'woocommerce'),
			'37' => __('Nadur', 'woocommerce'),
			'10' => __('Fontana', 'woocommerce'),
			'67' => __('Zejtun', 'woocommerce'),
			'20' => __('Senglea', 'woocommerce'),
			'27' => __('Marsaskala', 'woocommerce'),
			'6' => __('Cospicua', 'woocommerce'),
			'51' => __('St. Pauls Bay', 'woocommerce'),
			'32' => __('Mosta', 'woocommerce')
		);
		$states['RW'] = array(
			'5' => __('Southern Province', 'woocommerce'),
			'4' => __('Western Province', 'woocommerce'),
			'2' => __('Eastern Province', 'woocommerce'),
			'1' => __('Kigali district', 'woocommerce'),
			'3' => __('Northern Province', 'woocommerce')
		);
		$states['LI'] = array(
			'8' => __('Schellenberg', 'woocommerce'),
			'7' => __('Schaan', 'woocommerce'),
			'2' => __('Eschen', 'woocommerce'),
			'11' => __('Vaduz', 'woocommerce'),
			'6' => __('Ruggell', 'woocommerce'),
			'5' => __('Planken', 'woocommerce'),
			'4' => __('Mauren', 'woocommerce'),
			'10' => __('Triesenberg', 'woocommerce'),
			'3' => __('Gamprin', 'woocommerce'),
			'1' => __('Balzers', 'woocommerce'),
			'9' => __('Triesen', 'woocommerce')
		);
		$states['NO'] = array(
			'50' => __('Trøndelag', 'woocommerce'),
			'3' => __('Oslo', 'woocommerce'),
			'34' => __('Innlandet', 'woocommerce'),
			'30' => __('Viken', 'woocommerce'),
			'21' => __('Svalbard', 'woocommerce'),
			'42' => __('Agder', 'woocommerce'),
			'54' => __('Troms og Finnmark', 'woocommerce'),
			'46' => __('Vestland', 'woocommerce'),
			'15' => __('Møre og Romsdal', 'woocommerce'),
			'11' => __('Rogaland', 'woocommerce'),
			'38' => __('Vestfold og Telemark', 'woocommerce'),
			'18' => __('Nordland', 'woocommerce'),
			'22' => __('Jan Mayen', 'woocommerce')
		);
		$states['SG'] = array(
			'2' => __('North East', 'woocommerce'),
			'4' => __('South East', 'woocommerce'),
			'1' => __('Central Singapore', 'woocommerce'),
			'5' => __('South West', 'woocommerce'),
			'3' => __('North West', 'woocommerce')
		);
		$states['Sk'] = array(
			'BC' => __('Banská Bystrica Region', 'woocommerce'),
			'KI' => __('Košice Region', 'woocommerce'),
			'PV' => __('Prešov Region', 'woocommerce'),
			'TA' => __('Trnava Region', 'woocommerce'),
			'BL' => __('Bratislava Region', 'woocommerce'),
			'NI' => __('Nitra Region', 'woocommerce'),
			'TC' => __('Trencín Region', 'woocommerce'),
			'ZI' => __('Žilina Region', 'woocommerce')
		);
		$states['SI'] = array(
			'151' => __('Braslovce Municipality', 'woocommerce'),
			'58' => __('Lenart Municipality', 'woocommerce'),
			'171' => __('Oplotnica', 'woocommerce'),
			'134' => __('Velike Lašce Municipality', 'woocommerce'),
			'159' => __('Hajdina Municipality', 'woocommerce'),
			'92' => __('Podcetrtek Municipality', 'woocommerce'),
			'152' => __('Cankova Municipality', 'woocommerce'),
			'137' => __('Vitanje Municipality', 'woocommerce'),
			'111' => __('Sežana Municipality', 'woocommerce'),
			'45' => __('Kidricevo Municipality', 'woocommerce'),
			'15' => __('Crenšovci Municipality', 'woocommerce'),
			'36' => __('Idrija Municipality', 'woocommerce'),
			'185' => __('Trnovska Vas Municipality', 'woocommerce'),
			'138' => __('Vodice Municipality', 'woocommerce'),
			'103' => __('Ravne na Koroškem Municipality', 'woocommerce'),
			'167' => __('Lovrenc na Pohorju Municipality', 'woocommerce'),
			'69' => __('Majšperk Municipality', 'woocommerce'),
			'66' => __('Loški Potok Municipality', 'woocommerce'),
			'23' => __('Domžale Municipality', 'woocommerce'),
			'209' => __('Recica ob Savinji Municipality', 'woocommerce'),
			'172' => __('Podlehnik Municipality', 'woocommerce'),
			'13' => __('Cerknica Municipality', 'woocommerce'),
			'189' => __('Vransko Municipality', 'woocommerce'),
			'181' => __('Sveta Ana Municipality', 'woocommerce'),
			'8' => __('Brezovica Municipality', 'woocommerce'),
			'148' => __('Benedikt Municipality', 'woocommerce'),
			'19' => __('Divaca Municipality', 'woocommerce'),
			'77' => __('Moravce Municipality', 'woocommerce'),
			'112' => __('Slovenj Gradec City Municipality', 'woocommerce'),
			'121' => __('Škocjan Municipality', 'woocommerce'),
			'120' => __('Šentjur Municipality', 'woocommerce'),
			'89' => __('Pesnica Municipality', 'woocommerce'),
			'22' => __('Dol pri Ljubljani Municipality', 'woocommerce'),
			'65' => __('Loška Dolina Municipality', 'woocommerce'),
			'160' => __('Hoce–Slivnica Municipality', 'woocommerce'),
			'153' => __('Cerkvenjak Municipality', 'woocommerce'),
			'82' => __('Naklo Municipality', 'woocommerce'),
			'14' => __('Cerkno Municipality', 'woocommerce'),
			'149' => __('Bistrica ob Sotli Municipality', 'woocommerce'),
			'43' => __('Kamnik Municipality', 'woocommerce'),
			'6' => __('Bovec Municipality', 'woocommerce'),
			'143' => __('Zavrc Municipality', 'woocommerce'),
			'1' => __('Ajdovšcina Municipality', 'woocommerce'),
			'91' => __('Pivka Municipality', 'woocommerce'),
			'127' => __('Štore Municipality', 'woocommerce'),
			'51' => __('Kozje Municipality', 'woocommerce'),
			'123' => __('Municipality of Škofljica', 'woocommerce'),
			'174' => __('Prebold Municipality', 'woocommerce'),
			'156' => __('Dobrovnik Municipality', 'woocommerce'),
			'79' => __('Mozirje Municipality', 'woocommerce'),
			'11' => __('City Municipality of Celje', 'woocommerce'),
			'147' => __('Žiri Municipality', 'woocommerce'),
			'162' => __('Horjul Municipality', 'woocommerce'),
			'184' => __('Tabor Municipality', 'woocommerce'),
			'99' => __('Radece Municipality', 'woocommerce'),
			'136' => __('Vipava Municipality', 'woocommerce'),
			'55' => __('Kungota', 'woocommerce'),
			'114' => __('Slovenske Konjice Municipality', 'woocommerce'),
			'88' => __('Osilnica Municipality', 'woocommerce'),
			'5' => __('Borovnica Municipality', 'woocommerce'),
			'90' => __('Piran Municipality', 'woocommerce'),
			'3' => __('Bled Municipality', 'woocommerce'),
			'163' => __('Jezersko Municipality', 'woocommerce'),
			'98' => __('Race–Fram Municipality', 'woocommerce'),
			'84' => __('Nova Gorica City Municipality', 'woocommerce'),
			'176' => __('Razkrižje Municipality', 'woocommerce'),
			'177' => __('Ribnica na Pohorju Municipality', 'woocommerce'),
			'81' => __('Muta Municipality', 'woocommerce'),
			'107' => __('Rogatec Municipality', 'woocommerce'),
			'28' => __('Gorišnica Municipality', 'woocommerce'),
			'56' => __('Kuzma Municipality', 'woocommerce'),
			'76' => __('Mislinja Municipality', 'woocommerce'),
			'26' => __('Duplek Municipality', 'woocommerce'),
			'130' => __('Trebnje Municipality', 'woocommerce'),
			'9' => __('Brežice Municipality', 'woocommerce'),
			'20' => __('Dobrepolje Municipality', 'woocommerce'),
			'158' => __('Grad Municipality', 'woocommerce'),
			'78' => __('Moravske Toplice Municipality', 'woocommerce'),
			'67' => __('Luce Municipality', 'woocommerce'),
			'75' => __('Miren–Kostanjevica Municipality', 'woocommerce'),
			'87' => __('Ormož Municipality', 'woocommerce'),
			'33' => __('Šalovci Municipality', 'woocommerce'),
			'169' => __('Miklavž na Dravskem Polju Municipality', 'woocommerce'),
			'198' => __('Makole Municipality', 'woocommerce'),
			'59' => __('Lendava Municipality', 'woocommerce'),
			'141' => __('Vuzenica Municipality', 'woocommerce'),
			'44' => __('Kanal ob Soci Municipality', 'woocommerce'),
			'96' => __('Ptuj City Municipality', 'woocommerce'),
			'182' => __('Sveti Andraž v Slovenskih Goricah Municipality', 'woocommerce'),
			'178' => __('Selnica ob Dravi Municipality', 'woocommerce'),
			'102' => __('Radovljica Municipality', 'woocommerce'),
			'16' => __('Crna na Koroškem Municipality', 'woocommerce'),
			'106' => __('Rogaška Slatina Municipality', 'woocommerce'),
			'93' => __('Podvelka Municipality', 'woocommerce'),
			'104' => __('Ribnica Municipality', 'woocommerce'),
			'85' => __('City Municipality of Novo Mesto', 'woocommerce'),
			'170' => __('Mirna Pec Municipality', 'woocommerce'),
			'166' => __('Križevci Municipality', 'woocommerce'),
			'200' => __('Poljcane Municipality', 'woocommerce'),
			'7' => __('Brda Municipality', 'woocommerce'),
			'119' => __('Šentjernej Municipality', 'woocommerce'),
			'70' => __('Maribor City Municipality', 'woocommerce'),
			'46' => __('Kobarid Municipality', 'woocommerce'),
			'168' => __('Markovci Municipality', 'woocommerce'),
			'139' => __('Vojnik Municipality', 'woocommerce'),
			'129' => __('Trbovlje Municipality', 'woocommerce'),
			'128' => __('Tolmin Municipality', 'woocommerce'),
			'126' => __('Šoštanj Municipality', 'woocommerce'),
			'191' => __('Žetale Municipality', 'woocommerce'),
			'131' => __('Tržic Municipality', 'woocommerce'),
			'132' => __('Turnišce Municipality', 'woocommerce'),
			'155' => __('Dobrna Municipality', 'woocommerce'),
			'201' => __('Rence–Vogrsko Municipality', 'woocommerce'),
			'197' => __('Kostanjevica na Krki Municipality', 'woocommerce'),
			'116' => __('Sveti Jurij ob Šcavnici Municipality', 'woocommerce'),
			'146' => __('Železniki Municipality', 'woocommerce'),
			'188' => __('Veržej Municipality', 'woocommerce'),
			'190' => __('Žalec Municipality', 'woocommerce'),
			'115' => __('Starše Municipality', 'woocommerce'),
			'204' => __('Sveta Trojica v Slovenskih Goricah Municipality', 'woocommerce'),
			'180' => __('Solcava Municipality', 'woocommerce'),
			'140' => __('Vrhnika Municipality', 'woocommerce'),
			'202' => __('Središce ob Dravi', 'woocommerce'),
			'105' => __('Rogašovci Municipality', 'woocommerce'),
			'74' => __('Mežica Municipality', 'woocommerce'),
			'42' => __('Juršinci Municipality', 'woocommerce'),
			'187' => __('Velika Polana Municipality', 'woocommerce'),
			'110' => __('Sevnica Municipality', 'woocommerce'),
			'142' => __('Zagorje ob Savi Municipality', 'woocommerce'),
			'61' => __('Ljubljana City Municipality', 'woocommerce'),
			'31' => __('Gornji Petrovci Municipality', 'woocommerce'),
			'173' => __('Polzela Municipality', 'woocommerce'),
			'205' => __('Sveti Tomaž Municipality', 'woocommerce'),
			'175' => __('Prevalje Municipality', 'woocommerce'),
			'101' => __('Radlje ob Dravi Municipality', 'woocommerce'),
			'192' => __('Žirovnica Municipality', 'woocommerce'),
			'179' => __('Sodražica Municipality', 'woocommerce'),
			'150' => __('Bloke Municipality', 'woocommerce'),
			'194' => __('Šmartno pri Litiji Municipality', 'woocommerce'),
			'108' => __('Ruše Municipality', 'woocommerce'),
			'157' => __('Dolenjske Toplice Municipality', 'woocommerce'),
			'4' => __('Bohinj Municipality', 'woocommerce'),
			'164' => __('Komenda Municipality', 'woocommerce'),
			'207' => __('Gorje Municipality', 'woocommerce'),
			'124' => __('Šmarje pri Jelšah Municipality', 'woocommerce'),
			'37' => __('Ig Municipality', 'woocommerce'),
			'52' => __('Kranj City Municipality', 'woocommerce'),
			'97' => __('Puconci Municipality', 'woocommerce'),
			'206' => __('Šmarješke Toplice Municipality', 'woocommerce'),
			'24' => __('Dornava Municipality', 'woocommerce'),
			'17' => __('Crnomelj Municipality', 'woocommerce'),
			'100' => __('Radenci Municipality', 'woocommerce'),
			'27' => __('Gorenja Vas–Poljane Municipality', 'woocommerce'),
			'62' => __('Ljubno Municipality', 'woocommerce'),
			'154' => __('Dobje Municipality', 'woocommerce'),
			'125' => __('Šmartno ob Paki Municipality', 'woocommerce'),
			'199' => __('Mokronog–Trebelno Municipality', 'woocommerce'),
			'212' => __('Mirna Municipality', 'woocommerce'),
			'117' => __('Šencur Municipality', 'woocommerce'),
			'135' => __('Videm Municipality', 'woocommerce'),
			'2' => __('Beltinci Municipality', 'woocommerce'),
			'68' => __('Lukovica Municipality', 'woocommerce'),
			'95' => __('Preddvor Municipality', 'woocommerce'),
			'18' => __('Destrnik Municipality', 'woocommerce'),
			'39' => __('Ivancna Gorica Municipality', 'woocommerce'),
			'208' => __('Log–Dragomer Municipality', 'woocommerce'),
			'193' => __('Žužemberk Municipality', 'woocommerce'),
			'21' => __('Dobrova–Polhov Gradec Municipality', 'woocommerce'),
			'196' => __('Municipality of Cirkulane', 'woocommerce'),
			'12' => __('Cerklje na Gorenjskem Municipality', 'woocommerce'),
			'211' => __('Šentrupert Municipality', 'woocommerce'),
			'10' => __('Tišina Municipality', 'woocommerce'),
			'80' => __('Murska Sobota City Municipality', 'woocommerce'),
			'54' => __('Municipality of Krško', 'woocommerce'),
			'49' => __('Komen Municipality', 'woocommerce'),
			'122' => __('Škofja Loka Municipality', 'woocommerce'),
			'183' => __('Šempeter–Vrtojba Municipality', 'woocommerce'),
			'195' => __('Municipality of Apace', 'woocommerce'),
			'50' => __('Koper City Municipality', 'woocommerce'),
			'86' => __('Odranci Municipality', 'woocommerce'),
			'35' => __('Hrpelje–Kozina Municipality', 'woocommerce'),
			'40' => __('Izola Municipality', 'woocommerce'),
			'73' => __('Metlika Municipality', 'woocommerce'),
			'118' => __('Šentilj Municipality', 'woocommerce'),
			'47' => __('Kobilje Municipality', 'woocommerce'),
			'213' => __('Ankaran Municipality', 'woocommerce'),
			'161' => __('Hodoš Municipality', 'woocommerce'),
			'210' => __('Sveti Jurij v Slovenskih Goricah Municipality', 'woocommerce'),
			'83' => __('Nazarje Municipality', 'woocommerce'),
			'94' => __('Postojna Municipality', 'woocommerce'),
			'165' => __('Kostel Municipality', 'woocommerce'),
			'113' => __('Slovenska Bistrica Municipality', 'woocommerce'),
			'203' => __('Straža Municipality', 'woocommerce'),
			'186' => __('Trzin Municipality', 'woocommerce'),
			'48' => __('Kocevje Municipality', 'woocommerce'),
			'32' => __('Grosuplje Municipality', 'woocommerce'),
			'41' => __('Jesenice Municipality', 'woocommerce'),
			'57' => __('Laško Municipality', 'woocommerce'),
			'30' => __('Gornji Grad Municipality', 'woocommerce'),
			'53' => __('Kranjska Gora Municipality', 'woocommerce'),
			'34' => __('Hrastnik Municipality', 'woocommerce'),
			'144' => __('Zrece Municipality', 'woocommerce'),
			'29' => __('Gornja Radgona Municipality', 'woocommerce'),
			'38' => __('Municipality of Ilirska Bistrica', 'woocommerce'),
			'25' => __('Dravograd Municipality', 'woocommerce'),
			'109' => __('Semic Municipality', 'woocommerce'),
			'60' => __('Litija Municipality', 'woocommerce'),
			'72' => __('Mengeš Municipality', 'woocommerce'),
			'71' => __('Medvode Municipality', 'woocommerce'),
			'64' => __('Logatec Municipality', 'woocommerce'),
			'63' => __('Ljutomer Municipality', 'woocommerce')
		);
		$states['IL'] = array(
			'Z' => __('Northern District', 'woocommerce'),
			'M' => __('Central District', 'woocommerce'),
			'D' => __('Southern District', 'woocommerce'),
			'HA' => __('Haifa District', 'woocommerce'),
			'JM' => __('Jerusalem District', 'woocommerce'),
			'TA' => __('Tel Aviv District', 'woocommerce')
		);
		$states['BE'] = array(
			'VLI' => __('Limburg', 'woocommerce'),
			'VLG' => __('Flanders', 'woocommerce'),
			'VBR' => __('Flemish Brabant', 'woocommerce'),
			'WHT' => __('Hainaut', 'woocommerce'),
			'BRU' => __('Brussels-Capital Region', 'woocommerce'),
			'VOV' => __('East Flanders', 'woocommerce'),
			'WNA' => __('Namur', 'woocommerce'),
			'WLX' => __('Luxembourg', 'woocommerce'),
			'WAL' => __('Wallonia', 'woocommerce'),
			'VAN' => __('Antwerp', 'woocommerce'),
			'WBR' => __('Walloon Brabant', 'woocommerce'),
			'VWV' => __('West Flanders', 'woocommerce'),
			'WLG' => __('Liège', 'woocommerce')
		);
		$states['EE'] = array(
			'39' => __('Hiiu County', 'woocommerce'),
			'84' => __('Viljandi County', 'woocommerce'),
			'78' => __('Tartu County', 'woocommerce'),
			'82' => __('Valga County', 'woocommerce'),
			'70' => __('Rapla County', 'woocommerce'),
			'86' => __('Võru County', 'woocommerce'),
			'74' => __('Saare County', 'woocommerce'),
			'67' => __('Pärnu County', 'woocommerce'),
			'65' => __('Põlva County', 'woocommerce'),
			'59' => __('Lääne-Viru County', 'woocommerce'),
			'49' => __('Jõgeva County', 'woocommerce'),
			'51' => __('Järva County', 'woocommerce'),
			'37' => __('Harju County', 'woocommerce'),
			'57' => __('Lääne County', 'woocommerce'),
			'44' => __('Ida-Viru County', 'woocommerce')
		);
		$states['FI'] = array(
			'6' => __('Tavastia Proper', 'woocommerce'),
			'7' => __('Central Ostrobothnia', 'woocommerce'),
			'4' => __('Southern Savonia', 'woocommerce'),
			'5' => __('Kainuu', 'woocommerce'),
			'2' => __('South Karelia', 'woocommerce'),
			'3' => __('Southern Ostrobothnia', 'woocommerce'),
			'10' => __('Lapland', 'woocommerce'),
			'17' => __('Satakunta', 'woocommerce'),
			'16' => __('Päijänne Tavastia', 'woocommerce'),
			'15' => __('Northern Savonia', 'woocommerce'),
			'13' => __('North Karelia', 'woocommerce'),
			'14' => __('Northern Ostrobothnia', 'woocommerce'),
			'11' => __('Pirkanmaa', 'woocommerce'),
			'19' => __('Finland Proper', 'woocommerce'),
			'12' => __('Ostrobothnia', 'woocommerce'),
			'1' => __('Åland Islands', 'woocommerce'),
			'18' => __('Uusimaa', 'woocommerce'),
			'8' => __('Central Finland', 'woocommerce'),
			'9' => __('Kymenlaakso', 'woocommerce')
		);
		$states['VN'] = array(
			'66' => __('Hung Yen', 'woocommerce'),
			'45' => __('Dong Thap', 'woocommerce'),
			'43' => __('Ba Ria-Vung Tau', 'woocommerce'),
			'21' => __('Thanh Hoa', 'woocommerce'),
			'28' => __('Kon Tum', 'woocommerce'),
			'71' => __('Dien Bien', 'woocommerce'),
			'70' => __('Vinh Phuc', 'woocommerce'),
			'20' => __('Thai Bình', 'woocommerce'),
			'27' => __('Quang Nam', 'woocommerce'),
			'73' => __('Hau Giang', 'woocommerce'),
			'59' => __('Ca Mau', 'woocommerce'),
			'3' => __('Ha Giang', 'woocommerce'),
			'22' => __('Nghe An', 'woocommerce'),
			'46' => __('Tien Giang', 'woocommerce'),
			'4' => __('Cao Bang', 'woocommerce'),
			'HP' => __('Hai Phong', 'woocommerce'),
			'6' => __('Yen Bai', 'woocommerce'),
			'57' => __('Binh Duong', 'woocommerce'),
			'18' => __('Ninh Bình', 'woocommerce'),
			'40' => __('Binh Thuan', 'woocommerce'),
			'36' => __('Ninh Thuan', 'woocommerce'),
			'67' => __('Nam Dinh', 'woocommerce'),
			'49' => __('Vinh Long', 'woocommerce'),
			'56' => __('Bac Ninh', 'woocommerce'),
			'9' => __('Lang Son', 'woocommerce'),
			'34' => __('Khanh Hoa', 'woocommerce'),
			'44' => __('An Giang', 'woocommerce'),
			'7' => __('Tuyen Quang', 'woocommerce'),
			'50' => __('Ben Tre', 'woocommerce'),
			'58' => __('Bình Phuoc', 'woocommerce'),
			'26' => __('Thua Thien-Hue', 'woocommerce'),
			'14' => __('Hoa Binh', 'woocommerce'),
			'47' => __('Kien Giang', 'woocommerce'),
			'68' => __('Phu Tho', 'woocommerce'),
			'63' => __('Ha Nam', 'woocommerce'),
			'25' => __('Quang Tri', 'woocommerce'),
			'55' => __('Bac Lieu', 'woocommerce'),
			'51' => __('Tra Vinh', 'woocommerce'),
			'DN' => __('Da Nang', 'woocommerce'),
			'69' => __('Thai Nguyen', 'woocommerce'),
			'41' => __('Long An', 'woocommerce'),
			'24' => __('Quang Binh', 'woocommerce'),
			'HN' => __('Ha Noi', 'woocommerce'),
			'SG' => __('Ho Chi Minh', 'woocommerce'),
			'5' => __('Son La', 'woocommerce'),
			'30' => __('Gia Lai', 'woocommerce'),
			'13' => __('Quang Ninh', 'woocommerce'),
			'54' => __('Bac Giang', 'woocommerce'),
			'23' => __('Ha Tinh', 'woocommerce'),
			'2' => __('Lao Cai', 'woocommerce'),
			'35' => __('Lam Dong', 'woocommerce'),
			'52' => __('Soc Trang', 'woocommerce'),
			'39' => __('Dong Nai', 'woocommerce'),
			'53' => __('Bac Kan', 'woocommerce'),
			'72' => __('Dak Nong', 'woocommerce'),
			'32' => __('Phu Yen', 'woocommerce'),
			'1' => __('Lai Chau', 'woocommerce'),
			'37' => __('Tay Ninh', 'woocommerce'),
			'61' => __('Hai Duong', 'woocommerce'),
			'29' => __('Quang Ngai', 'woocommerce'),
			'33' => __('Dak Lak', 'woocommerce'),
			'31' => __('Binh Dinh', 'woocommerce'),
			'CT' => __('Can Tho', 'woocommerce')
		);

		$states['GF'] = array(
			'GF' => __('French Guiana', 'woocommerce')
		);
		$states['MF'] = array(
			'MF' => __('Saint-Martin French part', 'woocommerce')
		);
		$states['GP'] = array(
			'GP' => __('Guadeloupe', 'woocommerce')
		);
		$states['IM'] = array(
			'IM' => __('Isle of Man', 'woocommerce')
		);
		$states['MQ'] = array(
			'MQ' => __('Martinique', 'woocommerce')
		);
		$states['YT'] = array(
			'YT' => __('Mayotte', 'woocommerce')
		);
		$states['RE'] = array(
			'RE' => __('Reunion', 'woocommerce')
		);
		$states['KR'] = array(
			'27' => __('Daegu', 'woocommerce'),
			'41' => __('Gyeonggi Province', 'woocommerce'),
			'28' => __('Incheon', 'woocommerce'),
			'11' => __('Seoul', 'woocommerce'),
			'30' => __('Daejeon', 'woocommerce'),
			'45' => __('North Jeolla Province', 'woocommerce'),
			'31' => __('Ulsan', 'woocommerce'),
			'49' => __('Jeju', 'woocommerce'),
			'43' => __('North Chungcheong Province', 'woocommerce'),
			'47' => __('North Gyeongsang Province', 'woocommerce'),
			'46' => __('South Jeolla Province', 'woocommerce'),
			'48' => __('South Gyeongsang Province', 'woocommerce'),
			'29' => __('Gwangju', 'woocommerce'),
			'44' => __('South Chungcheong Province', 'woocommerce'),
			'26' => __('Busan', 'woocommerce'),
			'50' => __('Sejong City', 'woocommerce'),
			'42' => __('Gangwon Province', 'woocommerce')
		);
		$states['LU'] = array(
			'DI' => __('Canton of Diekirch', 'woocommerce'),
			'L' => __('Luxembourg District', 'woocommerce'),
			'EC' => __('Canton of Echternach', 'woocommerce'),
			'RD' => __('Canton of Redange', 'woocommerce'),
			'ES' => __('Canton of Esch-sur-Alzette', 'woocommerce'),
			'CA' => __('Canton of Capellen', 'woocommerce'),
			'RM' => __('Canton of Remich', 'woocommerce'),
			'G' => __('Grevenmacher District', 'woocommerce'),
			'CL' => __('Canton of Clervaux', 'woocommerce'),
			'ME' => __('Canton of Mersch', 'woocommerce'),
			'VD' => __('Canton of Vianden', 'woocommerce'),
			'D' => __('Diekirch District', 'woocommerce'),
			'GR' => __('Canton of Grevenmacher', 'woocommerce'),
			'WI' => __('Canton of Wiltz', 'woocommerce'),
			'LU' => __('Canton of Luxembourg', 'woocommerce')
		);
		$states['SE'] = array(
			'X' => __('Gävleborg County', 'woocommerce'),
			'W' => __('Dalarna County', 'woocommerce'),
			'S' => __('Värmland County', 'woocommerce'),
			'E' => __('Östergötland County', 'woocommerce'),
			'K' => __('Blekinge County', 'woocommerce'),
			'BD' => __('Norrbotten County', 'woocommerce'),
			'T' => __('Örebro County', 'woocommerce'),
			'D' => __('Södermanland County', 'woocommerce'),
			'M' => __('Skåne County', 'woocommerce'),
			'G' => __('Kronoberg County', 'woocommerce'),
			'AC' => __('Västerbotten County', 'woocommerce'),
			'H' => __('Kalmar County', 'woocommerce'),
			'C' => __('Uppsala County', 'woocommerce'),
			'I' => __('Gotland County', 'woocommerce'),
			'O' => __('Västra Götaland County', 'woocommerce'),
			'N' => __('Halland County', 'woocommerce'),
			'U' => __('Västmanland County', 'woocommerce'),
			'F' => __('Jönköping County', 'woocommerce'),
			'AB' => __('Stockholm County', 'woocommerce'),
			'Y' => __('Västernorrland County', 'woocommerce')
		);
		$states['PL'] = array(
			'OP' => __('Opole Voivodeship', 'woocommerce'),
			'SL' => __('Silesian Voivodeship', 'woocommerce'),
			'PM' => __('Pomeranian Voivodeship', 'woocommerce'),
			'KP' => __('Kuyavian-Pomeranian Voivodeship', 'woocommerce'),
			'PK' => __('Podkarpackie Voivodeship', 'woocommerce'),
			'WN' => __('Warmian-Masurian Voivodeship', 'woocommerce'),
			'DS' => __('Lower Silesian Voivodeship', 'woocommerce'),
			'SK' => __('Swietokrzyskie Voivodeship', 'woocommerce'),
			'LB' => __('Lubusz Voivodeship', 'woocommerce'),
			'PD' => __('Podlaskie Voivodeship', 'woocommerce'),
			'ZP' => __('West Pomeranian Voivodeship', 'woocommerce'),
			'WP' => __('Greater Poland Voivodeship', 'woocommerce'),
			'MA' => __('Lesser Poland Voivodeship', 'woocommerce'),
			'LD' => __('Lód´z Voivodeship', 'woocommerce'),
			'MZ' => __('Masovian Voivodeship', 'woocommerce'),
			'LU' => __('Lublin Voivodeship', 'woocommerce')
		);
		$states['PT'] = array(
			'11' => __('Lisbon', 'woocommerce'),
			'4' => __('Bragança', 'woocommerce'),
			'2' => __('Beja', 'woocommerce'),
			'30' => __('Madeira', 'woocommerce'),
			'12' => __('Portalegre', 'woocommerce'),
			'20' => __('Açores', 'woocommerce'),
			'17' => __('Vila Real', 'woocommerce'),
			'1' => __('Aveiro', 'woocommerce'),
			'7' => __('Évora', 'woocommerce'),
			'18' => __('Viseu', 'woocommerce'),
			'14' => __('Santarém', 'woocommerce'),
			'8' => __('Faro', 'woocommerce'),
			'10' => __('Leiria', 'woocommerce'),
			'5' => __('Castelo Branco', 'woocommerce'),
			'15' => __('Setúbal', 'woocommerce'),
			'13' => __('Porto', 'woocommerce'),
			'3' => __('Braga', 'woocommerce'),
			'16' => __('Viana do Castelo', 'woocommerce'),
			'6' => __('Coimbra', 'woocommerce')
		);

		$states['NL'] = array(
			'UT' => __('Utrecht', 'woocommerce'),
			'GE' => __('Gelderland', 'woocommerce'),
			'NH' => __('North Holland', 'woocommerce'),
			'DR' => __('Drenthe', 'woocommerce'),
			'ZH' => __('South Holland', 'woocommerce'),
			'LI' => __('Limburg', 'woocommerce'),
			'BQ3' => __('Sint Eustatius', 'woocommerce'),
			'GR' => __('Groningen', 'woocommerce'),
			'OV' => __('Overijssel', 'woocommerce'),
			'FL' => __('Flevoland', 'woocommerce'),
			'ZE' => __('Zeeland', 'woocommerce'),
			'BQ2' => __('Saba', 'woocommerce'),
			'FR' => __('Friesland', 'woocommerce'),
			'NB' => __('North Brabant', 'woocommerce'),
			'BQ1' => __('Bonaire', 'woocommerce')
		);
		$states['NL'] = array(
			'39' => __('Hiiu County', 'woocommerce'),
			'84' => __('Viljandi County', 'woocommerce'),
			'78' => __('Tartu County', 'woocommerce'),
			'82' => __('Valga County', 'woocommerce'),
			'70' => __('Rapla County', 'woocommerce'),
			'86' => __('Võru County', 'woocommerce'),
			'74' => __('Saare County', 'woocommerce'),
			'67' => __('Pärnu County', 'woocommerce'),
			'65' => __('Põlva County', 'woocommerce'),
			'59' => __('Lääne-Viru County', 'woocommerce'),
			'49' => __('Jõgeva County', 'woocommerce'),
			'51' => __('Järva County', 'woocommerce'),
			'37' => __('Harju County', 'woocommerce'),
			'57' => __('Lääne County', 'woocommerce'),
			'44' => __('Ida-Viru County', 'woocommerce')
		);

		$states['NL'] = array(
			'41' => __('Jaffna District', 'woocommerce'),
			'21' => __('Kandy District', 'woocommerce'),
			'13' => __('Kalutara District', 'woocommerce'),
			'81' => __('Badulla District', 'woocommerce'),
			'33' => __('Hambantota District', 'woocommerce'),
			'31' => __('Galle District', 'woocommerce'),
			'42' => __('Kilinochchi District', 'woocommerce'),
			'23' => __('Nuwara Eliya District', 'woocommerce'),
			'53' => __('Trincomalee District', 'woocommerce'),
			'62' => __('Puttalam District', 'woocommerce'),
			'92' => __('Kegalle District', 'woocommerce'),
			'2' => __('Central Province', 'woocommerce'),
			'52' => __('Ampara District', 'woocommerce'),
			'7' => __('North Central Province', 'woocommerce'),
			'3' => __('Southern Province', 'woocommerce'),
			'1' => __('Western Province', 'woocommerce'),
			'9' => __('Sabaragamuwa Province', 'woocommerce'),
			'12' => __('Gampaha District', 'woocommerce'),
			'43' => __('Mannar District', 'woocommerce'),
			'32' => __('Matara District', 'woocommerce'),
			'91' => __('Ratnapura district', 'woocommerce'),
			'5' => __('Eastern Province', 'woocommerce'),
			'44' => __('Vavuniya District', 'woocommerce'),
			'22' => __('Matale District', 'woocommerce'),
			'8' => __('Uva Province', 'woocommerce'),
			'72' => __('Polonnaruwa District', 'woocommerce'),
			'4' => __('Northern Province', 'woocommerce'),
			'45' => __('Mullaitivu District', 'woocommerce'),
			'11' => __('Colombo District', 'woocommerce'),
			'71' => __('Anuradhapura District', 'woocommerce'),
			'6' => __('North Western Province', 'woocommerce'),
			'51' => __('Batticaloa District', 'woocommerce'),
			'82' => __('Monaragala District', 'woocommerce')
		);
		$states['DK'] = array(
			'85' => __('Region Zealand', 'woocommerce'),
			'83' => __('Region of Southern Denmark', 'woocommerce'),
			'84' => __('Capital Region of Denmark', 'woocommerce'),
			'82' => __('Central Denmark Region', 'woocommerce'),
			'81' => __('North Denmark Region', 'woocommerce')
		);
		$states['BH'] = array(
			'13' => __('Capital', 'woocommerce'),
			'14' => __('Southern', 'woocommerce'),
			'17' => __('Northern', 'woocommerce'),
			'15' => __('Muharraq', 'woocommerce'),
			'16' => __('Central', 'woocommerce')
		);

		$states['BI'] = array(
			'RM' => __('Rumonge Province', 'woocommerce'),
			'MY' => __('Muyinga Province', 'woocommerce'),
			'MW' => __('Mwaro Province', 'woocommerce'),
			'MA' => __('Makamba Province', 'woocommerce'),
			'RT' => __('Rutana Province', 'woocommerce'),
			'CI' => __('Cibitoke Province', 'woocommerce'),
			'RY' => __('Ruyigi Province', 'woocommerce'),
			'KY' => __('Kayanza Province', 'woocommerce'),
			'MU' => __('Muramvya Province', 'woocommerce'),
			'KR' => __('Karuzi Province', 'woocommerce'),
			'KI' => __('Kirundo Province', 'woocommerce'),
			'BB' => __('Bubanza Province', 'woocommerce'),
			'GI' => __('Gitega Province', 'woocommerce'),
			'BM' => __('Bujumbura Mairie Province', 'woocommerce'),
			'NG' => __('Ngozi Province', 'woocommerce'),
			'BL' => __('Bujumbura Rural Province', 'woocommerce'),
			'CA' => __('Cankuzo Province', 'woocommerce'),
			'BR' => __('Bururi Province', 'woocommerce')
		);

		$states['LB'] = array(
			'JA' => __('South', 'woocommerce'),
			'JL' => __('Mount Lebanon', 'woocommerce'),
			'BH' => __('Baalbek-Hermel', 'woocommerce'),
			'AS' => __('North', 'woocommerce'),
			'AK' => __('Akkar', 'woocommerce'),
			'BA' => __('Beirut', 'woocommerce'),
			'BI' => __('Beqaa', 'woocommerce'),
			'NA' => __('Nabatieh', 'woocommerce')
		);
		$states['IS'] = array(
			'2' => __('Southern Peninsula Region', 'woocommerce'),
			'1' => __('Capital Region', 'woocommerce'),
			'4' => __('Westfjords', 'woocommerce'),
			'7' => __('Eastern Region', 'woocommerce'),
			'8' => __('Southern Region', 'woocommerce'),
			'5' => __('Northwestern Region', 'woocommerce'),
			'3' => __('Western Region', 'woocommerce'),
			'6' => __('Northeastern Region', 'woocommerce')
		);
		$states['FR'] = array(
			'BL' => __('Saint-Barthélemy', 'woocommerce'),
			'NAQ' => __('Nouvelle-Aquitaine', 'woocommerce'),
			'IDF' => __('Île-de-France', 'woocommerce'),
			'976' => __('Mayotte', 'woocommerce'),
			'ARA' => __('Auvergne-Rhône-Alpes', 'woocommerce'),
			'OCC' => __('Occitanie', 'woocommerce'),
			'PDL' => __('Pays-de-la-Loire', 'woocommerce'),
			'NOR' => __('Normandie', 'woocommerce'),
			'20R' => __('Corse', 'woocommerce'),
			'BRE' => __('Bretagne', 'woocommerce'),
			'MF' => __('Saint-Martin', 'woocommerce'),
			'WF' => __('Wallis and Futuna', 'woocommerce'),
			'6AE' => __('Alsace', 'woocommerce'),
			'PAC' => __('Provence-Alpes-Côte-d Azur', 'woocommerce'),
			'75C' => __('Paris', 'woocommerce'),
			'CVL' => __('Centre-Val de Loire', 'woocommerce'),
			'GES' => __('Grand-Est', 'woocommerce'),
			'PM' => __('Saint Pierre and Miquelon', 'woocommerce'),
			'973' => __('French Guiana', 'woocommerce'),
			'974' => __('La Réunion', 'woocommerce'),
			'PF' => __('French Polynesia', 'woocommerce'),
			'BFC' => __('Bourgogne-Franche-Comté', 'woocommerce'),
			'972' => __('Martinique', 'woocommerce'),
			'HDF' => __('Hauts-de-France', 'woocommerce'),
			'971' => __('Guadeloupe', 'woocommerce'),
			'01' => __('Ain', 'woocommerce'),
			'02' => __('Aisne', 'woocommerce'),
			'03' => __('Allier', 'woocommerce'),
			'04' => __('Alpes-de-Haute-Provence', 'woocommerce'),
			'05' => __('Hautes-Alpes', 'woocommerce'),
			'06' => __('Alpes-Maritimes', 'woocommerce'),
			'07' => __('Ardèche', 'woocommerce'),
			'08' => __('Ardennes', 'woocommerce'),
			'09' => __('Ariège', 'woocommerce'),
			'10' => __('Aube', 'woocommerce'),
			'11' => __('Aude', 'woocommerce'),
			'12' => __('Aveyron', 'woocommerce'),
			'13' => __('Bouches-du-Rhône', 'woocommerce'),
			'14' => __('Calvados', 'woocommerce'),
			'15' => __('Cantal', 'woocommerce'),
			'16' => __('Charente', 'woocommerce'),
			'17' => __('Charente-Maritime', 'woocommerce'),
			'18' => __('Cher', 'woocommerce'),
			'19' => __('Corrèze', 'woocommerce'),
			'21' => __('Côte-dOr', 'woocommerce'),
			'22' => __('Côtes-dArmor', 'woocommerce'),
			'23' => __('Creuse', 'woocommerce'),
			'24' => __('Dordogne', 'woocommerce'),
			'25' => __('Doubs', 'woocommerce'),
			'26' => __('Drôme', 'woocommerce'),
			'27' => __('Eure', 'woocommerce'),
			'28' => __('Eure-et-Loir', 'woocommerce'),
			'29' => __('Finistère', 'woocommerce'),
			'2A' => __('Corse-du-Sud', 'woocommerce'),
			'2B' => __('Haute-Corse', 'woocommerce'),
			'30' => __('Gard', 'woocommerce'),
			'31' => __('Haute-Garonne', 'woocommerce'),
			'32' => __('Gers', 'woocommerce'),
			'33' => __('Gironde', 'woocommerce'),
			'34' => __('Hérault', 'woocommerce'),
			'35' => __('Ille-et-Vilaine', 'woocommerce'),
			'36' => __('Indre', 'woocommerce'),
			'37' => __('Indre-et-Loire', 'woocommerce'),
			'38' => __('Isère', 'woocommerce'),
			'39' => __('Jura', 'woocommerce'),
			'40' => __('Landes', 'woocommerce'),
			'41' => __('Loir-et-Cher', 'woocommerce'),
			'42' => __('Loire', 'woocommerce'),
			'43' => __('Haute-Loire', 'woocommerce'),
			'44' => __('Loire-Atlantique', 'woocommerce'),
			'45' => __('Loiret', 'woocommerce'),
			'46' => __('Lot', 'woocommerce'),
			'47' => __('Lot-et-Garonne', 'woocommerce'),
			'48' => __('Lozère', 'woocommerce'),
			'49' => __('Maine-et-Loire', 'woocommerce'),
			'50' => __('Manche', 'woocommerce'),
			'51' => __('Marne', 'woocommerce'),
			'52' => __('Haute-Marne', 'woocommerce'),
			'53' => __('Mayenne', 'woocommerce'),
			'54' => __('Meurthe-et-Moselle', 'woocommerce'),
			'55' => __('Meuse', 'woocommerce'),
			'56' => __('Morbihan', 'woocommerce'),
			'57' => __('Moselle', 'woocommerce'),
			'58' => __('Nièvre', 'woocommerce'),
			'59' => __('Nord', 'woocommerce'),
			'60' => __('Oise', 'woocommerce'),
			'61' => __('Orne', 'woocommerce'),
			'62' => __('Pas-de-Calais', 'woocommerce'),
			'63' => __('Puy-de-Dôme', 'woocommerce'),
			'64' => __('Pyrénées-Atlantiques', 'woocommerce'),
			'65' => __('Hautes-Pyrénées', 'woocommerce'),
			'66' => __('Pyrénées-Orientales', 'woocommerce'),
			'67' => __('Bas-Rhin', 'woocommerce'),
			'68' => __('Haut-Rhin', 'woocommerce'),
			'69' => __('Rhône', 'woocommerce'),
			'69M' => __('Métropole de Lyon', 'woocommerce'),
			'70' => __('Haute-Saône', 'woocommerce'),
			'71' => __('Saône-et-Loire', 'woocommerce'),
			'72' => __('Sarthe', 'woocommerce'),
			'73' => __('Savoie', 'woocommerce'),
			'74' => __('Haute-Savoie', 'woocommerce'),
			'76' => __('Seine-Maritime', 'woocommerce'),
			'77' => __('Seine-et-Marne', 'woocommerce'),
			'78' => __('Yvelines', 'woocommerce'),
			'79' => __('Deux-Sèvres', 'woocommerce'),
			'80' => __('Somme', 'woocommerce'),
			'81' => __('Tarn', 'woocommerce'),
			'82' => __('Tarn-et-Garonne', 'woocommerce'),
			'83' => __('Var', 'woocommerce'),
			'84' => __('Vaucluse', 'woocommerce'),
			'85' => __('Vendée', 'woocommerce'),
			'86' => __('Vienne', 'woocommerce'),
			'87' => __('Haute-Vienne', 'woocommerce'),
			'88' => __('Vosges', 'woocommerce'),
			'89' => __('Yonne', 'woocommerce'),
			'90' => __('Territoire de Belfort', 'woocommerce'),
			'91' => __('Essonne', 'woocommerce'),
			'92' => __('Hauts-de-Seine', 'woocommerce'),
			'93' => __('Seine-Saint-Denis', 'woocommerce'),
			'94' => __('Val-de-Marne', 'woocommerce'),
			'95' => __('Val-dOise', 'woocommerce'),
			'CP' => __('Clipperton', 'woocommerce'),
			'TF' => __('French Southern and Antarctic Lands', 'woocommerce')
		);
		$states['PR'] = array(
			'SJ' => __('San Juan', 'woocommerce'),
			'BY' => __('Bayamon', 'woocommerce'),
			'CL' => __('Carolina', 'woocommerce'),
			'PO' => __('Ponce', 'woocommerce'),
			'CG' => __('Caguas', 'woocommerce'),
			'GN' => __('Guaynabo', 'woocommerce'),
			'AR' => __('Arecibo', 'woocommerce'),
			'TB' => __('Toa Baja', 'woocommerce'),
			'MG' => __('Mayagüez', 'woocommerce'),
			'TA' => __('Trujillo Alto', 'woocommerce'),
			'1' => __('Adjuntas', 'woocommerce'),
			'3' => __('Aguada', 'woocommerce'),
			'5' => __('Aguadilla', 'woocommerce'),
			'7' => __('Aguas Buenas', 'woocommerce'),
			'9' => __('Aibonito', 'woocommerce'),
			'11' => __('Añasco', 'woocommerce'),
			'13' => __('Arecibo', 'woocommerce'),
			'15' => __('Arroyo', 'woocommerce'),
			'17' => __('Barceloneta', 'woocommerce'),
			'19' => __('Barranquitas', 'woocommerce'),
			'21' => __('Bayamón', 'woocommerce'),
			'23' => __('Cabo Rojo', 'woocommerce'),
			'25' => __('Caguas', 'woocommerce'),
			'27' => __('Camuy', 'woocommerce'),
			'29' => __('Canóvanas', 'woocommerce'),
			'31' => __('Carolina', 'woocommerce'),
			'33' => __('Cataño', 'woocommerce'),
			'35' => __('Cayey', 'woocommerce'),
			'37' => __('Ceiba', 'woocommerce'),
			'39' => __('Ciales', 'woocommerce'),
			'41' => __('Cidra', 'woocommerce'),
			'43' => __('Coamo', 'woocommerce'),
			'45' => __('Comerío', 'woocommerce'),
			'47' => __('Corozal', 'woocommerce'),
			'49' => __('Culebra', 'woocommerce'),
			'51' => __('Dorado', 'woocommerce'),
			'53' => __('Fajardo', 'woocommerce'),
			'54' => __('Florida', 'woocommerce'),
			'55' => __('Guánica', 'woocommerce'),
			'57' => __('Guayama', 'woocommerce'),
			'59' => __('Guayanilla', 'woocommerce'),
			'61' => __('Guaynabo', 'woocommerce'),
			'63' => __('Gurabo', 'woocommerce'),
			'65' => __('Hatillo', 'woocommerce'),
			'67' => __('Hormigueros', 'woocommerce'),
			'69' => __('Humacao', 'woocommerce'),
			'71' => __('Isabela', 'woocommerce'),
			'73' => __('Jayuya', 'woocommerce'),
			'75' => __('Juana Díaz', 'woocommerce'),
			'77' => __('Juncos', 'woocommerce'),
			'79' => __('Lajas', 'woocommerce'),
			'81' => __('Lares', 'woocommerce'),
			'83' => __('Las Marías', 'woocommerce'),
			'85' => __('Las Piedras', 'woocommerce'),
			'87' => __('Loíza', 'woocommerce'),
			'89' => __('Luquillo', 'woocommerce'),
			'91' => __('Manatí', 'woocommerce'),
			'93' => __('Maricao', 'woocommerce'),
			'95' => __('Maunabo', 'woocommerce'),
			'97' => __('Mayagüez', 'woocommerce'),
			'99' => __('Moca', 'woocommerce'),
			'101' => __('Morovis', 'woocommerce'),
			'103' => __('Naguabo', 'woocommerce'),
			'105' => __('Naranjito', 'woocommerce'),
			'107' => __('Orocovis', 'woocommerce'),
			'109' => __('Patillas', 'woocommerce'),
			'111' => __('Peñuelas', 'woocommerce'),
			'113' => __('Ponce', 'woocommerce'),
			'115' => __('Quebradillas', 'woocommerce'),
			'117' => __('Rincón', 'woocommerce'),
			'119' => __('Río Grande', 'woocommerce'),
			'121' => __('Sabana Grande', 'woocommerce'),
			'123' => __('Salinas', 'woocommerce'),
			'125' => __('San Germán', 'woocommerce'),
			'127' => __('San Juan', 'woocommerce'),
			'129' => __('San Lorenzo', 'woocommerce'),
			'131' => __('San Sebastián', 'woocommerce'),
			'133' => __('Santa Isabel', 'woocommerce'),
			'135' => __('Toa Alta', 'woocommerce'),
			'137' => __('Toa Baja', 'woocommerce'),
			'139' => __('Trujillo Alto', 'woocommerce'),
			'141' => __('Utuado', 'woocommerce'),
			'143' => __('Vega Alta', 'woocommerce'),
			'145' => __('Vega Baja', 'woocommerce'),
			'147' => __('Vieques', 'woocommerce'),
			'149' => __('Villalba', 'woocommerce'),
			'151' => __('Yabucoa', 'woocommerce'),
			'153' => __('Yauco', 'woocommerce')
		);
		$states['CZ'] = array(
			'644' => __('Breclav', 'woocommerce'),
			'312' => __('Ceský Krumlov', 'woocommerce'),
			'323' => __('Plzen-mesto', 'woocommerce'),
			'643' => __('Brno-venkov', 'woocommerce'),
			'20B' => __('Príbram', 'woocommerce'),
			'532' => __('Pardubice', 'woocommerce'),
			'804' => __('Nový Jicín', 'woocommerce'),
			'523' => __('Náchod', 'woocommerce'),
			'713' => __('Prostejov', 'woocommerce'),
			'72' => __('Zlínský kraj', 'woocommerce'),
			'422' => __('Chomutov', 'woocommerce'),
			'20' => __('Stredoceský kraj', 'woocommerce'),
			'311' => __('Ceské Budejovice', 'woocommerce'),
			'20C' => __('Rakovník', 'woocommerce'),
			'802' => __('Frýdek-Místek', 'woocommerce'),
			'314' => __('Písek', 'woocommerce'),
			'645' => __('Hodonín', 'woocommerce'),
			'724' => __('Zlín', 'woocommerce'),
			'325' => __('Plzen-sever', 'woocommerce'),
			'317' => __('Tábor', 'woocommerce'),
			'642' => __('Brno-mesto', 'woocommerce'),
			'533' => __('Svitavy', 'woocommerce'),
			'723' => __('Vsetín', 'woocommerce'),
			'411' => __('Cheb', 'woocommerce'),
			'712' => __('Olomouc', 'woocommerce'),
			'63' => __('Kraj Vysocina', 'woocommerce'),
			'42' => __('Ústecký kraj', 'woocommerce'),
			'315' => __('Prachatice', 'woocommerce'),
			'525' => __('Trutnov', 'woocommerce'),
			'521' => __('Hradec Králové', 'woocommerce'),
			'41' => __('Karlovarský kraj', 'woocommerce'),
			'208' => __('Nymburk', 'woocommerce'),
			'326' => __('Rokycany', 'woocommerce'),
			'806' => __('Ostrava-mesto', 'woocommerce'),
			'803' => __('Karviná', 'woocommerce'),
			'53' => __('Pardubický kraj', 'woocommerce'),
			'71' => __('Olomoucký kraj', 'woocommerce'),
			'513' => __('Liberec', 'woocommerce'),
			'322' => __('Klatovy', 'woocommerce'),
			'722' => __('Uherské Hradište', 'woocommerce'),
			'721' => __('Kromeríž', 'woocommerce'),
			'413' => __('Sokolov', 'woocommerce'),
			'514' => __('Semily', 'woocommerce'),
			'634' => __('Trebíc', 'woocommerce'),
			'10' => __('Praha', 'woocommerce'),
			'427' => __('Ústí nad Labem', 'woocommerce'),
			'80' => __('Moravskoslezský kraj', 'woocommerce'),
			'51' => __('Liberecký kraj', 'woocommerce'),
			'64' => __('Jihomoravský kraj', 'woocommerce'),
			'412' => __('Karlovy Vary', 'woocommerce'),
			'423' => __('Litomerice', 'woocommerce'),
			'209' => __('Praha-východ', 'woocommerce'),
			'32' => __('Plzenský kraj', 'woocommerce'),
			'324' => __('Plzen-jih', 'woocommerce'),
			'421' => __('Decín', 'woocommerce'),
			'631' => __('Havlíckuv Brod', 'woocommerce'),
			'512' => __('Jablonec nad Nisou', 'woocommerce'),
			'632' => __('Jihlava', 'woocommerce'),
			'52' => __('Královéhradecký kraj', 'woocommerce'),
			'641' => __('Blansko', 'woocommerce'),
			'424' => __('Louny', 'woocommerce'),
			'204' => __('Kolín', 'woocommerce'),
			'20A' => __('Praha-západ', 'woocommerce'),
			'202' => __('Beroun', 'woocommerce'),
			'426' => __('Teplice', 'woocommerce'),
			'646' => __('Vyškov', 'woocommerce'),
			'805' => __('Opava', 'woocommerce'),
			'313' => __('Jindrichuv Hradec', 'woocommerce'),
			'711' => __('Jeseník', 'woocommerce'),
			'714' => __('Prerov', 'woocommerce'),
			'201' => __('Benešov', 'woocommerce'),
			'316' => __('Strakonice', 'woocommerce'),
			'425' => __('Most', 'woocommerce'),
			'647' => __('Znojmo', 'woocommerce'),
			'203' => __('Kladno', 'woocommerce'),
			'511' => __('Ceská Lípa', 'woocommerce'),
			'531' => __('Chrudim', 'woocommerce'),
			'524' => __('Rychnov nad Knežnou', 'woocommerce'),
			'206' => __('Melník', 'woocommerce'),
			'31' => __('Jihoceský kraj', 'woocommerce'),
			'522' => __('Jicín', 'woocommerce'),
			'321' => __('Domažlice', 'woocommerce'),
			'715' => __('Šumperk', 'woocommerce'),
			'207' => __('Mladá Boleslav', 'woocommerce'),
			'801' => __('Bruntál', 'woocommerce'),
			'633' => __('Pelhrimov', 'woocommerce'),
			'327' => __('Tachov', 'woocommerce'),
			'534' => __('Ústí nad Orlicí', 'woocommerce'),
			'635' => __('Ždár nad Sázavou', 'woocommerce'),
			'205' => __('Kutná Hora', 'woocommerce')
		);
		$states['KW'] = array(
			'JA' => __('Al Jahra', 'woocommerce'),
			'HA' => __('Hawalli', 'woocommerce'),
			'MU' => __('Mubarak Al-Kabeer', 'woocommerce'),
			'FA' => __('Al Farwaniyah', 'woocommerce'),
			'AH' => __('Al Ahmadi', 'woocommerce'),
			'KU' => __('Capital', 'woocommerce')
		);





		return $states;
	}



	public function bp_unrequire_wc_billing_state_field($fields)
	{
		$fields['billing_state']['required'] = true;
		$fields['billing_state']['hidden'] = false;
		return $fields;
	}

	public function bp_unrequire_wc_shipping_state_field($fields)
	{
		$fields['shipping_state']['required'] = true;
		$fields['shipping_state']['hidden'] = false;
		return $fields;
	}


	public function bulletproof_frontend_enqueue_scripts()
	{
		wp_enqueue_script('frontend-script', plugins_url('../assets/js/frontend.js', __FILE__), array('jquery'), '1.0', true);
	}

	public function bulletproof_admin_enqueue_custom_scripts()
	{
		wp_enqueue_script('admin-custom-script', plugins_url('../assets/js/admin-custom-script.js', __FILE__), array('jquery'), '1.0', true);

		wp_localize_script('admin-custom-script', 'custom_script_vars', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('order-payment-capture'),
		));
	}

	//Add column header
	public function bulletproof_checkout_capture_column_header($columns)
	{

		$columns['payment_capture_column'] = __('Features', 'bulletproof-checkout-lite');
		return $columns;
	}

	public function bulletproof_checkout_capture_column_content($column, $order)
	{
		if ('payment_capture_column' === $column) {
			if ($order && $order->get_status() === 'on-hold') {
				$transaction_id = $order->get_meta('_payment_gateway_tx_received', true);
				if ($transaction_id != "") {
					$sale_method_received = $order->get_meta('_bulletproof_gateway_action_type', true);
					if ($sale_method_received == "auth") {
						echo '<button class="button payment_capture_btn" data-order-id="' . esc_attr($order->get_id()) . '">Capture</button>';
					}
				}
			}
		}
	}

	public function bulletproof_checkout_capture_column_content_old($column, $order_id)
	{

		if ('payment_capture_column' === $column) {
			$order = wc_get_order($order_id);
			if ($order && $order->get_status() === 'on-hold') {
				$transaction_id = $order->get_meta('_payment_gateway_tx_received', true);
				if ($transaction_id != "") {
					$sale_method_received = $order->get_meta('_bulletproof_gateway_action_type', true);
					if ($sale_method_received == "auth") {
						echo '<button class="button payment_capture_btn" data-order-id="' . esc_attr($order_id) . '">Capture</button>';
					}
				}
			}
		}
	}

	public function bulletproof_capture_order_payment_callback()
	{
		// Verify the nonce
		check_ajax_referer('order-payment-capture', 'nonce');
		// Get the order ID from the AJAX request
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

		if ($order_id) {
			$order = new WC_Order($order_id);
			$gateway_settings = get_option('woocommerce_bulletproof_bpcheckout_lite_settings');
			$username = $gateway_settings['username'];
			$password = $gateway_settings['password'];
			$test_mode = $gateway_settings['testmode'];
			$security_key = $gateway_settings['api_key'];
			if (empty($username) || empty($password) || empty($security_key)) {
				wp_send_json_error('Username, password, or API key is empty.');
			}
			$request_args = array(
				'headers' => array(
					'accept' => 'application/json',
				),
				'body' => '',
			);

			// Locate the API endpoint to be used
			$base_api_url = "";
			try {
				if (($test_mode == "no") || ($test_mode == "")) {
					$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
				} else {
					$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL_SANDBOX;
				}
			} catch (Exception $e) {
				$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
			}

			$transaction_id = $order->get_meta('_payment_gateway_tx_received', true);
			$api_url = $base_api_url . 'capture_payment.php?user=' . urlencode($username) .
				'&pass=' . urlencode($password) .
				'&security_key=' . urlencode($security_key) .
				'&transactionid=' . urlencode($transaction_id);
			$data = array();
			$response = $this->bulletproof_capture_payment_api($api_url, $request_args);

			if (isset($response['data']) && !empty($response['data'])) {
				parse_str($response['data'], $responseArray);
				if (isset($responseArray['response']) && 1 == $responseArray['response']) {
					$order->payment_complete();

					$status_after_payment_completed = $this->get_option('status_after_order_completed');
					if ($status_after_payment_completed == "") $status_after_payment_completed = "completed";

					if ($status_after_payment_completed != "bp_donotchange") {
						$order->update_status($status_after_payment_completed, __('Status after capture payment updated by the BulletProof Plugin. ', 'bulletproof-checkout-lite'));
					}


					$order->save();
					wc_maybe_reduce_stock_levels($order_id);
					$data['success'] = true;
				} else {
					$data['success'] = false;
				}
			} else {
				$data['success'] = false;
				$data['message'] = $response['error'];
			}

			wp_send_json($data);
			die;
		}
	}


	public function woo_order_status_change_bpcheckout_lite($order_id, $old_status, $new_status)
	{
		if ($old_status != "") {
			$old_status = strtolower($old_status);
		}
		if ($new_status != "") {
			$new_status = strtolower($new_status);
		}

		// If the order changes from completed to cancelled or refunded , then will trigger a refund on the gateway
		//bulletproof_lite_gateway_api_refund_error

		if (($order_id != "") && ($old_status == "completed") && (($new_status == "cancelled") || ($new_status == "refunded"))) {
			error_log("Starting refund from the BulletProof Lite Plugin for the Order ID#:" . $order_id);
			// Check if the order was paid using the BulletProof Lite plugin (or the BulletProof plus plugin)
			$order = wc_get_order($order_id);
			//|| ! $order->get_transaction_id()
			if (! $order ||  !is_object($order)) {
				error_log("Invalid Order " . $order_id . " received.");
			} else {
				$payment_method_used = $order->get_meta('_payment_method', true);

				if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
					$date_completed = $order->get_date_completed();
					$datefrom = new DateTime($date_completed);
					$dateto = new DateTime();
					$days_diff = $datefrom->diff($dateto)->days;
					if ($days_diff < 30) {
						$lite_gateway = new Bulletproof_Payment_Gateway_Lite();
						$response_refund = $lite_gateway->process_refund($order_id, $order->get_total());

						if (is_wp_error($response_refund)) {

							$the_msg = "Order " . $order_id . " was not refunded.";

							$error_detail_on_gateway = "";

							if (isset($response_refund->errors['bulletproof_refund_api_error'][0])) {
								$error_detail_on_gateway = (string)$response_refund->errors['bulletproof_refund_api_error'][0];
								if ($error_detail_on_gateway != "") {
									$the_msg .= ". Detail:" . $error_detail_on_gateway;
								}
							}
							error_log($the_msg);
							if ($error_detail_on_gateway != "") {
								$order->add_order_note("This order can not be refunded by BulletProof because " . $error_detail_on_gateway);
								$order->save();
							}
							return false;
						} else {
							$the_msg = "Order " . $order_id . " was refunded succesfully";
							error_log($the_msg);
							try {
								$current_user = wp_get_current_user();
							} catch (Exception $ex) {
								$current_user = "";
							}
							$order->update_meta_data('_cancel_by',  $current_user);
							$order->update_meta_data('_bulletproof_refunded',  true);
						}
					} else {
						error_log("Order " . $order_id . " is older than 30 days and can not be refunded in the Payment Gateway");
						$order->add_order_note("This order is older than 30 days and can not be refunded from the BulletProof Checkout Plugin, but the status in WooCommerce was changed to Cancelled");
						$order->save();
					}
				} else {
					error_log("Order " . $order_id . " was not refunded by BulletProof because was originally paid with other payment gateway");
					$order->add_order_note("This order can not be refunded by BulletProof because was paid on another payment gateway");
					$order->save();
				}
			}
		}
	}



	/**
	 * Function to make API requests for capturing payment.
	 *
	 * @param string $api_url
	 * @param array $request_args
	 * @return array|mixed|object
	 */
	public function bulletproof_capture_payment_api($api_url, $request_args)
	{
		// API request logic for capture payment.
		$response = wp_remote_post($api_url, $request_args);

		if (is_wp_error($response)) {
			error_log('Capture payment API request failed: ' . $response->get_error_message());
			return $response->get_error_message();
		} else {
			$body = wp_remote_retrieve_body($response);
			$decoded_response = json_decode($body, true);
			return $decoded_response;
		}
	}

	public function check_bulletproof_lite_environment()
	{
		if (($_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php') && ($_SERVER['PHP_SELF'] != '/wp-admin/admin-post.php')) {
			// Socket timeout extended due to Mobile App users with slow hosting providers
			$socket_timeout = ini_get('default_socket_timeout'); // timeout in seconds
			if ($socket_timeout < 60) {
				ini_set('default_socket_timeout', 120);
			}

			$gateway_settings = get_option('woocommerce_bulletproof_bpcheckout_lite_settings');
			if (isset($gateway_settings['username'])) {
				$username = $gateway_settings['username'];
			} else {
				$username = "";
			}
			if (isset($gateway_settings['password'])) {
				$password = $gateway_settings['password'];
			} else {
				$password = "";
			}
			if (isset($gateway_settings['api_key'])) {
				$security_key = $gateway_settings['api_key'];
			} else {
				$security_key = "";
			}
			if (($username == "") || ($password == "") || ($security_key == "")) {
				// Add admin notice
				$adminnotice = new WC_Admin_Notices();
				$setting_link = admin_url('admin.php?page=wc-settings&tab=checkout&section=bulletproof_bpcheckout_lite');
				$adminnotice->add_custom_notice("", sprintf(__("BulletProof Checkout Lite is almost ready. To get started, <a href='%s'>set your BulletProof Checkout Lite account keys</a>.", 'wc-nmi'), $setting_link));
				$adminnotice->output_custom_notices();
			}
			if (class_exists('Jetpack') && Jetpack::is_module_active('notes')) {
				Jetpack::deactivate_module('notes');
			}
		}
	}
}
if (!isset($BP_shop_orders)) {
	$BP_shop_orders = new Bulletproof_Shop_Orders();
}
