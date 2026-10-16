<?php

namespace App\Controllers;

use App\Models\UserModel;

class Login extends BaseController
{
    public function form()
    {
        if ((new UserModel())->countAllResults() === 0) {
            return redirect()->to('/setup');
        }

        return view('login');
    }

    public function login()
    {
        $id   = trim((string) $this->request->getPost('email'));
        $pass = (string) $this->request->getPost('password');
        $user = (new UserModel())->groupStart()->where('email', $id)->orWhere('nip', $id)->groupEnd()->first();

        if (! $user || ! password_verify($pass, $user['password'])) {
            return redirect()->to('/login')->with('errors', 'Email/NIP atau kata sandi salah.')->withInput();
        }

        session()->regenerate();
        session()->set([
            'user_id'    => $user['nip'],
            'user_name'  => $user['username'],
            'user_email' => $user['email'],
            'user_role'  => (string) $user['jenis_user'],
            'logged_in'  => true,
        ]);

        return redirect()->to('/');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }

    /** Pemasangan pertama: membuat administrator bila belum ada pengguna sama sekali. */
    public function setup()
    {
        if ((new UserModel())->countAllResults() > 0) {
            return redirect()->to('/login');
        }

        return view('setup');
    }

    public function simpanSetup()
    {
        $model = new UserModel();
        if ($model->countAllResults() > 0) {
            return redirect()->to('/login');
        }
        if (! $this->validate([
            'nama'       => ['label' => 'Nama', 'rules' => 'required|max_length[100]'],
            'nip'        => ['label' => 'NIP', 'rules' => 'required|alpha_numeric|max_length[20]'],
            'email'      => ['label' => 'Email', 'rules' => 'required|valid_email|max_length[100]'],
            'password'   => ['label' => 'Kata sandi', 'rules' => 'required|min_length[8]'],
            'konfirmasi' => ['label' => 'Ulangi kata sandi', 'rules' => 'required|matches[password]'],
        ])) {
            return redirect()->to('/setup')->with('errors', $this->validator->getErrors())->withInput();
        }

        $kini = date('Y-m-d H:i:s');
        $model->insert([
            'nip'        => (string) $this->request->getPost('nip'),
            'username'   => trim((string) $this->request->getPost('nama')),
            'email'      => trim((string) $this->request->getPost('email')),
            'password'   => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'jenis_user' => '1',
            'created_at' => $kini,
            'updated_at' => $kini,
        ]);

        return redirect()->to('/login')->with('success', 'Administrator dibuat. Silakan masuk.');
    }
}
