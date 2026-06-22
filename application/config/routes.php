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
$route['api/auth/login'] = 'Auth/login';
$route['api/auth/logout'] = 'Auth/logout';
$route['api/auth/me'] = 'Auth/me';

// Dashboard Endpoints
$route['api/dashboard/staffTU'] = 'Dashboard/staffTU';
$route['api/dashboard/siswa'] = 'Dashboard/siswa';

// User Endpoints
$route['api/users/(:any)'] = 'Users/handle/$1';
$route['api/users'] = 'Users/handle';

// Mapel Endpoints
$route['api/mapel/(:any)'] = 'Mapel/handle/$1';
$route['api/mapel'] = 'Mapel/handle';

// Kelas Endpoints
$route['api/kelas/(:any)'] = 'Kelas/handle/$1';
$route['api/kelas'] = 'Kelas/handle';

// Materi Endpoints
$route['api/siswa/materi'] = 'Materi/materi_siswa';
$route['api/materi/(:any)/detail'] = 'Materi/detail_materi/$1';
$route['api/materi/(:any)/progress'] = 'Materi/update_progress/$1';

// Soal Endpoints
$route['api/siswa/soal'] = 'Materi/soal_siswa';
$route['api/siswa/progress/(:any)'] = 'Latihan/progress_latihan/$1';
$route['api/siswa/update/(:any)/progress'] = 'Latihan/update_progress_latihan/$1';
$route['api/latihan/jawab'] = 'Latihan/jawab';
$route['api/latihan/(:any)/selesai'] = 'Latihan/selesai/$1';
$route['api/latihan/(:any)'] = 'Latihan/detail/$1';$route['api/dashboard/guru'] = 'Dashboard/guru';
$route['api/dashboard/orang-tua'] = 'Dashboard/orang_tua';

// Modul Endpoints
$route['api/modul'] = 'Modul/index';
$route['api/modul/(:any)'] = 'Modul/detail/$1';

// Materi Endpoints
$route['api/materi'] = 'Materi/index';
$route['api/materi/(:any)'] = 'Materi/detail/$1';

// Soal dan Jawaban Endpoints
$route['api/soal'] = 'Soal/index';
$route['api/soal/(:any)'] = 'Soal/detail/$1';
$route['api/soal/(:any)/jawaban'] = 'Soal/jawaban/$1';

// Laporan Endpoints
$route['api/laporan'] = 'Laporan/index';
$route['api/laporan/siswa/(:any)'] = 'Laporan/siswa/$1';
$route['api/laporan-anak'] = 'Dashboard/laporan_anak';