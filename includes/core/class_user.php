<?php

class User {

    // GENERAL

    public static function user_full_info($user_id) {
        // vars
        $plots = Plot::select_plots_list($user_id);
        $exist_plots = Plot::plots_list_users($user_id);
        // info
        $q = DB::query("SELECT user_id, phone, first_name, last_name, phone, email
            FROM users WHERE user_id = " . $user_id . " LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'plots' => $plots,
                'exist_plots_str' => str_replace(' ', '', $exist_plots),
                'exist_plots_arr' => !empty($exist_plots) ? explode(', ', $exist_plots) : [],
            ];
        } else {
            return [
                'id' => 0,
                'first_name' => '',
                'last_name' => '',
                'phone' => '',
                'email' => '',
                'plots' => $plots,
                'exist_plots_str' => str_replace(' ', '', $exist_plots),
                'exist_plots_arr' => !empty($exist_plots) ? explode(', ', $exist_plots) : [],
            ];
        }
    }

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, access, first_name, last_name, phone, email
            FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access'],
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0,
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];

        // where
        $where = [];

        if ($search) {
            $where[] = "phone LIKE '%".$search."%'";
            $where[] = "first_name LIKE '%".$search."%'";
            $where[] = "email LIKE '%".$search."%'";
        }

        $where = $where ? "WHERE ".implode(" OR ", $where) : "";

        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, phone, email, last_login, updated
            FROM users ".$where." ORDER BY user_id LIMIT ".$offset.", ".$limit.";") or die (DB::error());

        while ($row = DB::fetch_row($q)) {
            $exist_plot_ids = Plot::plots_list_users($row['user_id']);
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => !empty($exist_plot_ids) ? $exist_plot_ids : '-',
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => (int) $row['phone'],
                'email' => $row['email'],
                'last_login' => (int) $row['last_login'],
                'updated' => date('Y/m/d', $row['updated'])
            ];
        }

        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users';
        if ($search) $url .= '?search='.$search.'&';
        paginator($count, $offset, $limit, $url, $paginator);

        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = []) {
        $info = User::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', User::user_full_info($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function user_edit_update($d = []) {
        // vars
        $errors = [];
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $first_name = isset($d['first_name']) && trim($d['first_name'])  ? trim($d['first_name']) : '';
        $last_name = isset($d['last_name']) && trim($d['last_name']) ? trim($d['last_name']) : '';
        $phone = isset($d['phone']) && trim($d['phone']) ? trim($d['phone']) : '';
        $email = isset($d['email']) && trim($d['email']) ? strtolower(trim($d['email'])) : '';
        $plot_ids = isset($d['plot_ids']) && trim($d['plot_ids']) ? trim($d['plot_ids']) : '';
        $plot_ids_arr = !empty($plot_ids) ? explode(',', $plot_ids) : [];
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;

        if (empty($first_name)) $errors['first_name'] = 'Required field';
        if (empty($last_name)) $errors['last_name'] = 'Required field';
        if (empty($phone)) $errors['phone'] = 'Required field';
        if (empty($email)) $errors['email'] = 'Required field';
        if (!preg_match("/^[0-9]{11}$/", $phone)) $errors['phone'] = 'Incorrect value';
        if (!preg_match("/^[0-9a-zA-Z-_.]+@[a-z]+\.[a-z]{2,3}$/", $email)) $errors['email'] = 'Incorrect value';

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        // update
        if ($user_id) {
            $set = [];
            $set[] = "first_name='".$first_name."'";
            $set[] = "last_name='".$last_name."'";
            $set[] = "phone='".$phone."'";
            $set[] = "email='".$email."'";
            $set[] = "phone_code=1111";
            $set[] = "updated='".Session::$ts."'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET ".$set." WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
        } else {
            DB::query("INSERT INTO users (
                first_name,
                last_name,
                phone,
                email,
                phone_code,
                updated
            ) VALUES (
                '".$first_name."',
                '".$last_name."',
                '".$phone."',
                '".$email."',
                '1111',
                '".Session::$ts."'
            );") or die (DB::error());
            $user_id = DB::last_insert_id();
        }

        DB::query("DELETE FROM users_plots WHERE user_id = '" . $user_id . "';") or die (DB::error());

        foreach ($plot_ids_arr as $plot_id) {
            DB::query("INSERT INTO users_plots (
                user_id,
                plot_id
            ) VALUES (
                '".$user_id."',
                '".$plot_id."'
            );") or die (DB::error());
        }

        // output
        return User::users_fetch(['offset' => $offset]);
    }

    public static function user_delete($d = []) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        // delete
        DB::query("DELETE FROM users WHERE user_id = '" . $user_id . "';") or die (DB::error());
        // output
        return User::users_fetch(['offset' => $offset]);
    }
}
