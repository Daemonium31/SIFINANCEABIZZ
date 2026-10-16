<?php

namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController
{
    public const PERAN = ['1' => 'Administrator', '2' => 'Akuntan', '3' => 'Pimpinan'];

    public function index()
    {
        return view('users', [
            'nav'   => 'user',
            'judul' => 'Pengguna',
            'users' => (new UserModel())->orderBy('jenis_user')->orderBy('username')->findAll(),
            'peran' => self::PERAN,
        ]);
    }

    public function simpan()
    {
        $lama = (string) $this->request->getPost('nip_lama');
        $aturan = [
            'nip'        => ['label' => 'NIP', 'rules' => 'required|alpha_numeric|max_length[20]|is_unique[users.nip' . ($lama !== '' ? ',nip,' . $lama : '') . ']'],
            'nama'       => ['label' => 'Nama', 'rules' => 'required|max_length[100]'],
            'email'      => ['label' => 'Email', 'rules' => 'required|valid_email|max_length[100]|is_unique[users.email' . ($lama !== '' ? ',nip,' . $lama : '') . ']'],
            'jenis_user' => ['label' => 'Peran', 'rules' => 'required|in_list[1,2,3]'],
            'password'   => ['label' => 'Kata sandi', 'rules' => ($lama !== '' ? 'permit_empty' : 'required') . '|min_length[8]'],
        ];
        if (! $this->validate($aturan)) {
            return redirect()->to('/user')->with('errors', $this->validator->getErrors())->withInput();
        }

        $nip  = (string) $this->request->getPost('nip');
        $data = [
            'nip'        => $nip,
            'username'   => trim((string) $this->request->getPost('nama')),
            'email'      => trim((string) $this->request->getPost('email')),
            'jenis_user' => (string) $this->request->getPost('jenis_user'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ((string) $this->request->getPost('password') !== '') {
            $data['password'] = password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        $model = new UserModel();
        if ($lama !== '') {
            if ($lama === session('user_id') && $data['jenis_user'] !== '1') {
                return redirect()->to('/user')->with('errors', 'Peran administrator Anda sendiri tidak bisa diturunkan dari halaman ini.');
            }
            $model->update($lama, $data);
            if ($lama === session('user_id')) {
                session()->set(['user_id' => $nip, 'user_name' => $data['username'], 'user_email' => $data['email']]);
            }
        } else {
            $model->insert($data + ['created_at' => $data['updated_at']]);
        }

        return redirect()->to('/user')->with('success', ($lama !== '' ? 'Pengguna diperbarui: ' : 'Pengguna ditambahkan: ') . $data['username']);
    }

    public function hapus()
    {
        $nip = (string) $this->request->getPost('nip');
        if ($nip === session('user_id')) {
            return redirect()->to('/user')->with('errors', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        }
        (new UserModel())->delete($nip);

        return redirect()->to('/user')->with('success', 'Pengguna dihapus.');
    }

    public function profile()
    {
        return view('profile', [
            'nav'     => 'profil',
            'judul'   => 'Profil Saya',
            'profile' => (new UserModel())->find(session('user_id')),
            'peran'   => self::PERAN,
        ]);
    }

    public function updateProfile()
    {
        $nip = (string) session('user_id');
        if (! $this->validate([
            'nama'  => ['label' => 'Nama', 'rules' => 'required|max_length[100]'],
            'email' => ['label' => 'Email', 'rules' => "required|valid_email|is_unique[users.email,nip,{$nip}]"],
        ])) {
            return redirect()->to('/profile')->with('errors', $this->validator->getErrors());
        }
        $data = ['username' => trim((string) $this->request->getPost('nama')), 'email' => trim((string) $this->request->getPost('email'))];
        (new UserModel())->update($nip, $data);
        session()->set(['user_name' => $data['username'], 'user_email' => $data['email']]);

        return redirect()->to('/profile')->with('success', 'Profil diperbarui.');
    }

    public function changePassword()
    {
        $model = new UserModel();
        $user  = $model->find(session('user_id'));
        if (! $this->validate([
            'password_lama'       => ['label' => 'Kata sandi saat ini', 'rules' => 'required'],
            'password_baru'       => ['label' => 'Kata sandi baru', 'rules' => 'required|min_length[8]'],
            'konfirmasi_password' => ['label' => 'Konfirmasi kata sandi', 'rules' => 'required|matches[password_baru]'],
        ])) {
            return redirect()->to('/profile')->with('errors', $this->validator->getErrors());
        }
        if (! $user || ! password_verify((string) $this->request->getPost('password_lama'), $user['password'])) {
            return redirect()->to('/profile')->with('errors', 'Kata sandi saat ini salah.');
        }
        $model->update($user['nip'], ['password' => password_hash((string) $this->request->getPost('password_baru'), PASSWORD_DEFAULT)]);

        return redirect()->to('/profile')->with('success', 'Kata sandi diganti.');
    }
}
