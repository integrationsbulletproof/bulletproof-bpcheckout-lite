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
		add_action('admin_enqueue_scripts', array($this, 'bulletproof_admin_enqueue_custom_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'bulletproof_frontend_enqueue_scripts'));

		if (BULLETPROOF_CHECKOUT_ADDORDERLISTCOLUMNS) {
			add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'bulletproof_checkout_capture_column_header'), 10, 1);
			add_filter('manage_edit-shop_order_columns', array($this, 'bulletproof_checkout_capture_column_header'), 10, 1);
			add_action('manage_shop_order_posts_custom_column', array($this, 'bulletproof_checkout_capture_column_content_old'), 10, 2);
			add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'bulletproof_checkout_capture_column_content'), 10, 2);
			add_action('wp_ajax_capture_order_payment', array($this, 'bulletproof_capture_order_payment_callback'));
		}

		// State is not a required field
		add_filter('woocommerce_shipping_fields', array($this, 'bp_unrequire_wc_shipping_state_field'));
		add_filter('woocommerce_billing_fields', array($this, 'bp_unrequire_wc_billing_state_field'));
		//	add_filter('default_checkout_billing_state', array($this, 'bp_remove_default_billing_state'));
		//	add_filter('default_checkout_shipping_state', array($this, 'bp_remove_default_billing_state'));
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

		// Add all the states missed by Woo , for example: Kuwait
		// AF
		$states['AF'] = array(
			'BDS' => 'Badakhshan',
			'BDG' => 'Badghis',
			'BGL' => 'Baghlan',
			'BAL' => 'Balkh',
			'BAM' => 'Bamyan',
			'DAY' => 'Daykundi',
			'FRA' => 'Farah',
			'FYB' => 'Faryab',
			'GHA' => 'Ghazni'     // There are some other missed states here
		);
		// Austria
		$states['AT'] = array(
			'1' => 'Burgenland',
			'2' => 'Carinthia',
			'3' => 'Lower Austria',
			'5' => 'Salzburg',
			'6' => 'Styria',
			'7' => 'Tyrol',
			'4' => 'Upper Austria',
			'9' => 'Vienna',
			'8' => 'Vorarlberg'
		);
		$states['ET'] = array(
			'SO' => 'Somali Region',
			'AM' => 'Amhara Region',
			'TI' => 'Tigray Region',
			'OR' => 'Oromia Region',
			'AF' => 'Afar Region',
			'HA' => 'Harari Region',
			'DD' => 'Dire Dawa',
			'BE' => 'Benishangul-Gumuz Region',
			'GA' => 'Gambela Region',
			'AA' => 'Addis Ababa'
		);
		$states['MT'] = array(
			'33' => 'Mqabba',
			'49' => 'San Gwann',
			'68' => 'Zurrieq',
			'25' => 'Luqa',
			'28' => 'Marsaxlokk',
			'42' => 'Qala',
			'66' => 'Zebbug Malta',
			'63' => 'Xghajra',
			'23' => 'Kirkop',
			'46' => 'Rabat',
			'9' => 'Floriana',
			'65' => 'Zebbug Gozo',
			'57' => 'Swieqi',
			'50' => 'Saint Lawrence',
			'5' => 'Birzebbuga',
			'29' => 'Mdina',
			'54' => 'Santa Venera',
			'22' => 'Kercem',
			'14' => 'Gharb',
			'19' => 'Iklin',
			'53' => 'Santa Lucija',
			'60' => 'Valletta',
			'34' => 'Msida',
			'4' => 'Birkirkara',
			'55' => 'Siggiewi',
			'21' => 'Kalkara',
			'48' => 'St. Julians',
			'45' => 'Victoria',
			'30' => 'Mellieha',
			'59' => 'Tarxien',
			'56' => 'Sliema',
			'18' => 'Hamrun',
			'16' => 'Ghasri',
			'3' => 'Birgu',
			'2' => 'Balzan',
			'31' => 'Mgarr',
			'1' => 'Attard',
			'44' => 'Qrendi',
			'38' => 'Naxxar',
			'12' => 'Gzira',
			'61' => 'Xaghra',
			'39' => 'Paola',
			'52' => 'Sannat',
			'7' => 'Dingli',
			'11' => 'Gudja',
			'43' => 'Qormi',
			'15' => 'Gharghur',
			'62' => 'Xewkija',
			'58' => 'Ta Xbiex',
			'64' => 'Zabbar',
			'17' => 'Ghaxaq',
			'40' => 'Pembroke',
			'24' => 'Lija',
			'41' => 'Pieta',
			'26' => 'Marsa',
			'8' => 'Fgura',
			'13' => 'Ghajnsielem',
			'35' => 'Mtarfa',
			'36' => 'Munxar',
			'37' => 'Nadur',
			'10' => 'Fontana',
			'67' => 'Zejtun',
			'20' => 'Senglea',
			'27' => 'Marsaskala',
			'6' => 'Cospicua',
			'51' => 'St. Pauls Bay',
			'32' => 'Mosta'
		);
		$states['RW'] = array(
			'5' => 'Southern Province',
			'4' => 'Western Province',
			'2' => 'Eastern Province',
			'1' => 'Kigali district',
			'3' => 'Northern Province'
		);
		$states['LI'] = array(
			'8' => 'Schellenberg',
			'7' => 'Schaan',
			'2' => 'Eschen',
			'11' => 'Vaduz',
			'6' => 'Ruggell',
			'5' => 'Planken',
			'4' => 'Mauren',
			'10' => 'Triesenberg',
			'3' => 'Gamprin',
			'1' => 'Balzers',
			'9' => 'Triesen'
		);
		$states['NO'] = array(
			'50' => 'Trøndelag',
			'3' => 'Oslo',
			'34' => 'Innlandet',
			'30' => 'Viken',
			'21' => 'Svalbard',
			'42' => 'Agder',
			'54' => 'Troms og Finnmark',
			'46' => 'Vestland',
			'15' => 'Møre og Romsdal',
			'11' => 'Rogaland',
			'38' => 'Vestfold og Telemark',
			'18' => 'Nordland',
			'22' => 'Jan Mayen'
		);

		$states['IL'] = array(
			'Z' => 'Northern District',
			'M' => 'Central District',
			'D' => 'Southern District',
			'HA' => 'Haifa District',
			'JM' => 'Jerusalem District',
			'TA' => 'Tel Aviv District'
		);
		$states['BE'] = array(
			'VLI' => 'Limburg',
			'VLG' => 'Flanders',
			'VBR' => 'Flemish Brabant',
			'WHT' => 'Hainaut',
			'BRU' => 'Brussels-Capital Region',
			'VOV' => 'East Flanders',
			'WNA' => 'Namur',
			'WLX' => 'Luxembourg',
			'WAL' => 'Wallonia',
			'VAN' => 'Antwerp',
			'WBR' => 'Walloon Brabant',
			'VWV' => 'West Flanders',
			'WLG' => 'Liège'
		);
		$states['FI'] = array(
			'6' => 'Tavastia Proper',
			'7' => 'Central Ostrobothnia',
			'4' => 'Southern Savonia',
			'5' => 'Kainuu',
			'2' => 'South Karelia',
			'3' => 'Southern Ostrobothnia',
			'10' => 'Lapland',
			'17' => 'Satakunta',
			'16' => 'Päijänne Tavastia',
			'15' => 'Northern Savonia',
			'13' => 'North Karelia',
			'14' => 'Northern Ostrobothnia',
			'11' => 'Pirkanmaa',
			'19' => 'Finland Proper',
			'12' => 'Ostrobothnia',
			'1' => 'Åland Islands',
			'18' => 'Uusimaa',
			'8' => 'Central Finland',
			'9' => 'Kymenlaakso'
		);
		$states['LU'] = array(
			'DI' => 'Canton of Diekirch',
			'L' => 'Luxembourg District',
			'EC' => 'Canton of Echternach',
			'RD' => 'Canton of Redange',
			'ES' => 'Canton of Esch-sur-Alzette',
			'CA' => 'Canton of Capellen',
			'RM' => 'Canton of Remich',
			'G' => 'Grevenmacher District',
			'CL' => 'Canton of Clervaux',
			'ME' => 'Canton of Mersch',
			'VD' => 'Canton of Vianden',
			'D' => 'Diekirch District',
			'GR' => 'Canton of Grevenmacher',
			'WI' => 'Canton of Wiltz',
			'LU' => 'Canton of Luxembourg'
		);
		$states['SE'] = array(
			'X' => 'Gävleborg County',
			'W' => 'Dalarna County',
			'S' => 'Värmland County',
			'E' => 'Östergötland County',
			'K' => 'Blekinge County',
			'BD' => 'Norrbotten County',
			'T' => 'Örebro County',
			'D' => 'Södermanland County',
			'M' => 'Skåne County',
			'G' => 'Kronoberg County',
			'AC' => 'Västerbotten County',
			'H' => 'Kalmar County',
			'C' => 'Uppsala County',
			'I' => 'Gotland County',
			'O' => 'Västra Götaland County',
			'N' => 'Halland County',
			'U' => 'Västmanland County',
			'F' => 'Jönköping County',
			'AB' => 'Stockholm County',
			'Y' => 'Västernorrland County'
		);
		$states['PL'] = array(
			'OP' => 'Opole Voivodeship',
			'SL' => 'Silesian Voivodeship',
			'PM' => 'Pomeranian Voivodeship',
			'KP' => 'Kuyavian-Pomeranian Voivodeship',
			'PK' => 'Podkarpackie Voivodeship',
			'WN' => 'Warmian-Masurian Voivodeship',
			'DS' => 'Lower Silesian Voivodeship',
			'SK' => 'Swietokrzyskie Voivodeship',
			'LB' => 'Lubusz Voivodeship',
			'PD' => 'Podlaskie Voivodeship',
			'ZP' => 'West Pomeranian Voivodeship',
			'WP' => 'Greater Poland Voivodeship',
			'MA' => 'Lesser Poland Voivodeship',
			'LD' => 'Lód´z Voivodeship',
			'MZ' => 'Masovian Voivodeship',
			'LU' => 'Lublin Voivodeship'
		);
		$states['PT'] = array(
			'11' => 'Lisbon',
			'4' => 'Bragança',
			'2' => 'Beja',
			'30' => 'Madeira',
			'12' => 'Portalegre',
			'20' => 'Açores',
			'17' => 'Vila Real',
			'1' => 'Aveiro',
			'7' => 'Évora',
			'18' => 'Viseu',
			'14' => 'Santarém',
			'8' => 'Faro',
			'10' => 'Leiria',
			'5' => 'Castelo Branco',
			'15' => 'Setúbal',
			'13' => 'Porto',
			'3' => 'Braga',
			'16' => 'Viana do Castelo',
			'6' => 'Coimbra'
		);

		$states['NL'] = array(
			'UT' => 'Utrecht',
			'GE' => 'Gelderland',
			'NH' => 'North Holland',
			'DR' => 'Drenthe',
			'ZH' => 'South Holland',
			'LI' => 'Limburg',
			'BQ3' => 'Sint Eustatius',
			'GR' => 'Groningen',
			'OV' => 'Overijssel',
			'FL' => 'Flevoland',
			'ZE' => 'Zeeland',
			'BQ2' => 'Saba',
			'FR' => 'Friesland',
			'NB' => 'North Brabant',
			'BQ1' => 'Bonaire'
		);
		$states['NL'] = array(
			'39' => 'Hiiu County',
			'84' => 'Viljandi County',
			'78' => 'Tartu County',
			'82' => 'Valga County',
			'70' => 'Rapla County',
			'86' => 'Võru County',
			'74' => 'Saare County',
			'67' => 'Pärnu County',
			'65' => 'Põlva County',
			'59' => 'Lääne-Viru County',
			'49' => 'Jõgeva County',
			'51' => 'Järva County',
			'37' => 'Harju County',
			'57' => 'Lääne County',
			'44' => 'Ida-Viru County'
		);

		$states['NL'] = array(
			'41' => 'Jaffna District',
			'21' => 'Kandy District',
			'13' => 'Kalutara District',
			'81' => 'Badulla District',
			'33' => 'Hambantota District',
			'31' => 'Galle District',
			'42' => 'Kilinochchi District',
			'23' => 'Nuwara Eliya District',
			'53' => 'Trincomalee District',
			'62' => 'Puttalam District',
			'92' => 'Kegalle District',
			'2' => 'Central Province',
			'52' => 'Ampara District',
			'7' => 'North Central Province',
			'3' => 'Southern Province',
			'1' => 'Western Province',
			'9' => 'Sabaragamuwa Province',
			'12' => 'Gampaha District',
			'43' => 'Mannar District',
			'32' => 'Matara District',
			'91' => 'Ratnapura district',
			'5' => 'Eastern Province',
			'44' => 'Vavuniya District',
			'22' => 'Matale District',
			'8' => 'Uva Province',
			'72' => 'Polonnaruwa District',
			'4' => 'Northern Province',
			'45' => 'Mullaitivu District',
			'11' => 'Colombo District',
			'71' => 'Anuradhapura District',
			'6' => 'North Western Province',
			'51' => 'Batticaloa District',
			'82' => 'Monaragala District'
		);
		$states['DK'] = array(
			'85' => 'Region Zealand',
			'83' => 'Region of Southern Denmark',
			'84' => 'Capital Region of Denmark',
			'82' => 'Central Denmark Region',
			'81' => 'North Denmark Region',
		);
		$states['BH'] = array(
			'13' => 'Capital',
			'14' => 'Southern',
			'17' => 'Northern',
			'15' => 'Muharraq',
			'16' => 'Central'
		);

		$states['BI'] = array(
			'RM' => 'Rumonge Province',
			'MY' => 'Muyinga Province',
			'MW' => 'Mwaro Province',
			'MA' => 'Makamba Province',
			'RT' => 'Rutana Province',
			'CI' => 'Cibitoke Province',
			'RY' => 'Ruyigi Province',
			'KY' => 'Kayanza Province',
			'MU' => 'Muramvya Province',
			'KR' => 'Karuzi Province',
			'KI' => 'Kirundo Province',
			'BB' => 'Bubanza Province',
			'GI' => 'Gitega Province',
			'BM' => 'Bujumbura Mairie Province',
			'NG' => 'Ngozi Province',
			'BL' => 'Bujumbura Rural Province',
			'CA' => 'Cankuzo Province',
			'BR' => 'Bururi Province'
		);

		$states['LB'] = array(
			'JA' => 'South',
			'JL' => 'Mount Lebanon',
			'BH' => 'Baalbek-Hermel',
			'AS' => 'North',
			'AK' => 'Akkar',
			'BA' => 'Beirut',
			'BI' => 'Beqaa',
			'NA' => 'Nabatieh'
		);
		$states['IS'] = array(
			'2' => 'Southern Peninsula Region',
			'1' => 'Capital Region',
			'4' => 'Westfjords',
			'7' => 'Eastern Region',
			'8' => 'Southern Region',
			'5' => 'Northwestern Region',
			'3' => 'Western Region',
			'6' => 'Northeastern Region'
		);
		$states['FR'] = array(
			'BL' => 'Saint-Barthélemy',
			'NAQ' => 'Nouvelle-Aquitaine',
			'IDF' => 'Île-de-France',
			'976' => 'Mayotte',
			'ARA' => 'Auvergne-Rhône-Alpes',
			'OCC' => 'Occitanie',
			'PDL' => 'Pays-de-la-Loire',
			'NOR' => 'Normandie',
			'20R' => 'Corse',
			'BRE' => 'Bretagne',
			'MF' => 'Saint-Martin',
			'WF' => 'Wallis and Futuna',
			'6AE' => 'Alsace',
			'PAC' => 'Provence-Alpes-Côte-d Azur',
			'75C' => 'Paris',
			'CVL' => 'Centre-Val de Loire',
			'GES' => 'Grand-Est',
			'PM' => 'Saint Pierre and Miquelon',
			'973' => 'French Guiana',
			'974' => 'La Réunion',
			'PF' => 'French Polynesia',
			'BFC' => 'Bourgogne-Franche-Comté',
			'972' => 'Martinique',
			'HDF' => 'Hauts-de-France',
			'971' => 'Guadeloupe',
			'01' => 'Ain',
			'02' => 'Aisne',
			'03' => 'Allier',
			'04' => 'Alpes-de-Haute-Provence',
			'05' => 'Hautes-Alpes',
			'06' => 'Alpes-Maritimes',
			'07' => 'Ardèche',
			'08' => 'Ardennes',
			'09' => 'Ariège',
			'10' => 'Aube',
			'11' => 'Aude',
			'12' => 'Aveyron',
			'13' => 'Bouches-du-Rhône',
			'14' => 'Calvados',
			'15' => 'Cantal',
			'16' => 'Charente',
			'17' => 'Charente-Maritime',
			'18' => 'Cher',
			'19' => 'Corrèze',
			'21' => 'Côte-dOr',
			'22' => 'Côtes-dArmor',
			'23' => 'Creuse',
			'24' => 'Dordogne',
			'25' => 'Doubs',
			'26' => 'Drôme',
			'27' => 'Eure',
			'28' => 'Eure-et-Loir',
			'29' => 'Finistère',
			'2A' => 'Corse-du-Sud',
			'2B' => 'Haute-Corse',
			'30' => 'Gard',
			'31' => 'Haute-Garonne',
			'32' => 'Gers',
			'33' => 'Gironde',
			'34' => 'Hérault',
			'35' => 'Ille-et-Vilaine',
			'36' => 'Indre',
			'37' => 'Indre-et-Loire',
			'38' => 'Isère',
			'39' => 'Jura',
			'40' => 'Landes',
			'41' => 'Loir-et-Cher',
			'42' => 'Loire',
			'43' => 'Haute-Loire',
			'44' => 'Loire-Atlantique',
			'45' => 'Loiret',
			'46' => 'Lot',
			'47' => 'Lot-et-Garonne',
			'48' => 'Lozère',
			'49' => 'Maine-et-Loire',
			'50' => 'Manche',
			'51' => 'Marne',
			'52' => 'Haute-Marne',
			'53' => 'Mayenne',
			'54' => 'Meurthe-et-Moselle',
			'55' => 'Meuse',
			'56' => 'Morbihan',
			'57' => 'Moselle',
			'58' => 'Nièvre',
			'59' => 'Nord',
			'60' => 'Oise',
			'61' => 'Orne',
			'62' => 'Pas-de-Calais',
			'63' => 'Puy-de-Dôme',
			'64' => 'Pyrénées-Atlantiques',
			'65' => 'Hautes-Pyrénées',
			'66' => 'Pyrénées-Orientales',
			'67' => 'Bas-Rhin',
			'68' => 'Haut-Rhin',
			'69' => 'Rhône',
			'69M' => 'Métropole de Lyon',
			'70' => 'Haute-Saône',
			'71' => 'Saône-et-Loire',
			'72' => 'Sarthe',
			'73' => 'Savoie',
			'74' => 'Haute-Savoie',
			'76' => 'Seine-Maritime',
			'77' => 'Seine-et-Marne',
			'78' => 'Yvelines',
			'79' => 'Deux-Sèvres',
			'80' => 'Somme',
			'81' => 'Tarn',
			'82' => 'Tarn-et-Garonne',
			'83' => 'Var',
			'84' => 'Vaucluse',
			'85' => 'Vendée',
			'86' => 'Vienne',
			'87' => 'Haute-Vienne',
			'88' => 'Vosges',
			'89' => 'Yonne',
			'90' => 'Territoire de Belfort',
			'91' => 'Essonne',
			'92' => 'Hauts-de-Seine',
			'93' => 'Seine-Saint-Denis',
			'94' => 'Val-de-Marne',
			'95' => 'Val-dOise',
			'CP' => 'Clipperton',
			'TF' => 'French Southern and Antarctic Lands'
		);
		$states['PR'] = array(
			'SJ' => 'San Juan',
			'BY' => 'Bayamon',
			'CL' => 'Carolina',
			'PO' => 'Ponce',
			'CG' => 'Caguas',
			'GN' => 'Guaynabo',
			'AR' => 'Arecibo',
			'TB' => 'Toa Baja',
			'MG' => 'Mayagüez',
			'TA' => 'Trujillo Alto',
			'1' => 'Adjuntas',
			'3' => 'Aguada',
			'5' => 'Aguadilla',
			'7' => 'Aguas Buenas',
			'9' => 'Aibonito',
			'11' => 'Añasco',
			'13' => 'Arecibo',
			'15' => 'Arroyo',
			'17' => 'Barceloneta',
			'19' => 'Barranquitas',
			'21' => 'Bayamón',
			'23' => 'Cabo Rojo',
			'25' => 'Caguas',
			'27' => 'Camuy',
			'29' => 'Canóvanas',
			'31' => 'Carolina',
			'33' => 'Cataño',
			'35' => 'Cayey',
			'37' => 'Ceiba',
			'39' => 'Ciales',
			'41' => 'Cidra',
			'43' => 'Coamo',
			'45' => 'Comerío',
			'47' => 'Corozal',
			'49' => 'Culebra',
			'51' => 'Dorado',
			'53' => 'Fajardo',
			'54' => 'Florida',
			'55' => 'Guánica',
			'57' => 'Guayama',
			'59' => 'Guayanilla',
			'61' => 'Guaynabo',
			'63' => 'Gurabo',
			'65' => 'Hatillo',
			'67' => 'Hormigueros',
			'69' => 'Humacao',
			'71' => 'Isabela',
			'73' => 'Jayuya',
			'75' => 'Juana Díaz',
			'77' => 'Juncos',
			'79' => 'Lajas',
			'81' => 'Lares',
			'83' => 'Las Marías',
			'85' => 'Las Piedras',
			'87' => 'Loíza',
			'89' => 'Luquillo',
			'91' => 'Manatí',
			'93' => 'Maricao',
			'95' => 'Maunabo',
			'97' => 'Mayagüez',
			'99' => 'Moca',
			'101' => 'Morovis',
			'103' => 'Naguabo',
			'105' => 'Naranjito',
			'107' => 'Orocovis',
			'109' => 'Patillas',
			'111' => 'Peñuelas',
			'113' => 'Ponce',
			'115' => 'Quebradillas',
			'117' => 'Rincón',
			'119' => 'Río Grande',
			'121' => 'Sabana Grande',
			'123' => 'Salinas',
			'125' => 'San Germán',
			'127' => 'San Juan',
			'129' => 'San Lorenzo',
			'131' => 'San Sebastián',
			'133' => 'Santa Isabel',
			'135' => 'Toa Alta',
			'137' => 'Toa Baja',
			'139' => 'Trujillo Alto',
			'141' => 'Utuado',
			'143' => 'Vega Alta',
			'145' => 'Vega Baja',
			'147' => 'Vieques',
			'149' => 'Villalba',
			'151' => 'Yabucoa',
			'153' => 'Yauco'
		);
		$states['CZ'] = array(
			'644' => 'Breclav',
			'312' => 'Ceský Krumlov',
			'323' => 'Plzen-mesto',
			'643' => 'Brno-venkov',
			'20B' => 'Príbram',
			'532' => 'Pardubice',
			'804' => 'Nový Jicín',
			'523' => 'Náchod',
			'713' => 'Prostejov',
			'72' => 'Zlínský kraj',
			'422' => 'Chomutov',
			'20' => 'Stredoceský kraj',
			'311' => 'Ceské Budejovice',
			'20C' => 'Rakovník',
			'802' => 'Frýdek-Místek',
			'314' => 'Písek',
			'645' => 'Hodonín',
			'724' => 'Zlín',
			'325' => 'Plzen-sever',
			'317' => 'Tábor',
			'642' => 'Brno-mesto',
			'533' => 'Svitavy',
			'723' => 'Vsetín',
			'411' => 'Cheb',
			'712' => 'Olomouc',
			'63' => 'Kraj Vysocina',
			'42' => 'Ústecký kraj',
			'315' => 'Prachatice',
			'525' => 'Trutnov',
			'521' => 'Hradec Králové',
			'41' => 'Karlovarský kraj',
			'208' => 'Nymburk',
			'326' => 'Rokycany',
			'806' => 'Ostrava-mesto',
			'803' => 'Karviná',
			'53' => 'Pardubický kraj',
			'71' => 'Olomoucký kraj',
			'513' => 'Liberec',
			'322' => 'Klatovy',
			'722' => 'Uherské Hradište',
			'721' => 'Kromeríž',
			'413' => 'Sokolov',
			'514' => 'Semily',
			'634' => 'Trebíc',
			'10' => 'Praha',
			'427' => 'Ústí nad Labem',
			'80' => 'Moravskoslezský kraj',
			'51' => 'Liberecký kraj',
			'64' => 'Jihomoravský kraj',
			'412' => 'Karlovy Vary',
			'423' => 'Litomerice',
			'209' => 'Praha-východ',
			'32' => 'Plzenský kraj',
			'324' => 'Plzen-jih',
			'421' => 'Decín',
			'631' => 'Havlíckuv Brod',
			'512' => 'Jablonec nad Nisou',
			'632' => 'Jihlava',
			'52' => 'Královéhradecký kraj',
			'641' => 'Blansko',
			'424' => 'Louny',
			'204' => 'Kolín',
			'20A' => 'Praha-západ',
			'202' => 'Beroun',
			'426' => 'Teplice',
			'646' => 'Vyškov',
			'805' => 'Opava',
			'313' => 'Jindrichuv Hradec',
			'711' => 'Jeseník',
			'714' => 'Prerov',
			'201' => 'Benešov',
			'316' => 'Strakonice',
			'425' => 'Most',
			'647' => 'Znojmo',
			'203' => 'Kladno',
			'511' => 'Ceská Lípa',
			'531' => 'Chrudim',
			'524' => 'Rychnov nad Knežnou',
			'206' => 'Melník',
			'31' => 'Jihoceský kraj',
			'522' => 'Jicín',
			'321' => 'Domažlice',
			'715' => 'Šumperk',
			'207' => 'Mladá Boleslav',
			'801' => 'Bruntál',
			'633' => 'Pelhrimov',
			'327' => 'Tachov',
			'534' => 'Ústí nad Orlicí',
			'635' => 'Ždár nad Sázavou',
			'205' => 'Kutná Hora'
		);
		$states['KW'] = array(
			'JA' => 'Al Jahra',
			'HA' => 'Hawalli',
			'MU' => 'Mubarak Al-Kabeer',
			'FA' => 'Al Farwaniyah',
			'AH' => 'Al Ahmadi',
			'KU' => 'Capital'
		);

		// The next countries are not supported:
		//AX
		//GF
		//GP
		//IM
		//KR
		//MF
		//MQ
		//RE
		//SG
		//SK
		//SI
		//VN
		//YT



		return $states;
	}

	public function bp_remove_default_billing_state()
	{
		return '';
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
}
new Bulletproof_Shop_Orders();
