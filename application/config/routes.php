<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth Endpoints
$route['auth/login'] = 'Auth/login';
$route['auth/logout'] = 'Auth/logout';
$route['auth/me'] = 'Auth/me';

// Dashboard Endpoints
$route['dashboard/staffTU'] = 'Dashboard/staffTU';
$route['dashboard/siswa'] = 'Dashboard/siswa';

// User Endpoints
$route['users'] = 'Users/handle';
$route['users/(:any)'] = 'Users/handle/$1';

// Mapel Endpoints
$route['mapel'] = 'Mapel/handle';
$route['mapel/(:any)'] = 'Mapel/handle/$1';

// Kelas Endpoints
$route['kelas'] = 'Kelas/handle';
$route['kelas/(:any)'] = 'Kelas/handle/$1';

// Materi Endpoints
$route['siswa/materi'] = 'Materi/materi_siswa';
$route['materi/(:any)/detail'] = 'Materi/detail_materi/$1';
$route['materi/(:any)/progress'] = 'Materi/update_progress/$1';

$route['materi'] = 'Materi/index';
$route['materi/(:any)/details'] = 'Materi/pages/$1';
$route['detail-materi/(:any)/gambar'] = 'Materi/page_image/$1';
$route['detail-materi/(:any)'] = 'Materi/page_detail/$1';
$route['materi/(:any)'] = 'Materi/detail/$1';

// Soal Endpoints
$route['siswa/soal'] = 'Materi/soal_siswa';
$route['siswa/progress/(:any)'] = 'Latihan/progress_latihan/$1';
$route['siswa/update/(:any)/progress'] = 'Latihan/update_progress_latihan/$1';
$route['latihan/jawab'] = 'Latihan/jawab';
$route['latihan/(:any)/selesai'] = 'Latihan/selesai/$1';
$route['latihan/(:any)'] = 'Latihan/detail/$1';
$route['dashboard/guru'] = 'Dashboard/guru';
$route['dashboard/orang-tua'] = 'Dashboard/orang_tua';

// Modul Endpoints
$route['modul'] = 'Modul/index';
$route['modul/(:any)'] = 'Modul/detail/$1';

// Soal dan Jawaban Endpoints
$route['soal'] = 'Soal/index';
$route['soal/(:any)/gambar'] = 'Soal/gambar/$1';
$route['soal/(:any)'] = 'Soal/detail/$1';
$route['soal/(:any)/jawaban'] = 'Soal/jawaban/$1';

// Laporan Endpoints
$route['laporan'] = 'Laporan/index';
$route['laporan/guru'] = 'Laporan/guru';
$route['laporan/siswa/(:any)'] = 'Laporan/siswa/$1';
$route['laporan-anak'] = 'Dashboard/laporan_anak';
