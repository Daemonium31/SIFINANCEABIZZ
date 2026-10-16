<?php namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php'))
{
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// Masuk & pemasangan pertama
$routes->get('/login', 'Login::form', ['filter' => 'guest']);
$routes->post('/login', 'Login::login', ['filter' => 'guest']);
$routes->get('/setup', 'Login::setup', ['filter' => 'guest']);
$routes->post('/setup', 'Login::simpanSetup', ['filter' => 'guest']);
$routes->get('/logout', 'Login::logout');

// Dashboard
$routes->get('/', 'Home::dashboard', ['filter' => 'auth']);
$routes->post('/dashboard/pdf', 'Home::pdf', ['filter' => 'auth']);

// Pengguna (administrator), cadangan (administrator, akuntan)
$routes->get('/user', 'User::index', ['filter' => 'role:1']);
$routes->post('/user/simpan', 'User::simpan', ['filter' => 'role:1']);
$routes->post('/user/hapus', 'User::hapus', ['filter' => 'role:1']);
$routes->get('/pengaturan', 'Pengaturan::index', ['filter' => 'role:1']);
$routes->post('/pengaturan/simpan', 'Pengaturan::simpan', ['filter' => 'role:1']);
$routes->get('/identitas/logo', 'Pengaturan::logo');
$routes->get('/cadangan', 'Cadangan::index', ['filter' => 'role:1,2']);
$routes->get('/cadangan/unduh', 'Cadangan::unduh', ['filter' => 'role:1,2']);
$routes->post('/cadangan/pulihkan', 'Cadangan::pulihkan', ['filter' => 'role:1,2']);
$routes->get('/cadangan/unduh-otomatis', 'Cadangan::unduhOtomatis', ['filter' => 'role:1,2']);
$routes->post('/cadangan/pulihkan-otomatis', 'Cadangan::pulihkanOtomatis', ['filter' => 'role:1,2']);

// Daftar akun & jurnal (administrator, akuntan)
$routes->get('/akun', 'Akun::index', ['filter' => 'role:1,2']);
$routes->post('/akun/simpan', 'Akun::simpan', ['filter' => 'role:1,2']);
$routes->post('/akun/hapus', 'Akun::hapus', ['filter' => 'role:1,2']);

$routes->get('/jurnal_umum', 'JurnalUmum::index', ['filter' => 'role:1,2']);
$routes->post('/jurnal_umum/simpan', 'JurnalUmum::simpan', ['filter' => 'role:1,2']);
$routes->post('/jurnal_umum/hapus', 'JurnalUmum::hapus', ['filter' => 'role:1,2']);

$routes->get('/jurnal_penyesuaian', 'JurnalPenyesuaian::index', ['filter' => 'role:1,2']);
$routes->post('/jurnal_penyesuaian/simpan', 'JurnalPenyesuaian::simpan', ['filter' => 'role:1,2']);
$routes->post('/jurnal_penyesuaian/hapus', 'JurnalPenyesuaian::hapus', ['filter' => 'role:1,2']);

// Profil
$routes->get('/profile', 'User::profile', ['filter' => 'auth']);
$routes->post('/profile/update', 'User::updateProfile', ['filter' => 'auth']);
$routes->post('/profile/changePass', 'User::changePassword', ['filter' => 'auth']);

// Laporan siklus akuntansi
$routes->get('/laporan', 'Laporan::index', ['filter' => 'auth']);
$routes->get('/laporan/pdf', 'Laporan::pdf', ['filter' => 'auth']);

/**
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
{
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
