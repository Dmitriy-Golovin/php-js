let common = {

    // vars

    modal_progress: false,
    modal_open: false,

    // common

    init: () => {
        add_event(document, 'mousedown touchstart', common.auto_hide_modal);
        add_event(document, 'click', () => common.menu_popup_hide_all('inactive', event));
        add_event(document, 'scroll', () => common.menu_popup_hide_all('all', event));
    },

    menu_popup_toggle: (el, e) => {
        el = qs('.menu_popup', el);
        if (has_class(el, 'active') && !e.target.closest('.menu_popup')) remove_class(el, 'active');
        else {
            common.menu_popup_hide_all('all');
            add_class(el, 'active');
        }
        if (e.target.tagName !== 'A') cancel_event(e);
    },

    menu_popup_hide_all: (mode, e) => {
        qs_all('.menu_popup.active').forEach((el) => {
            if (mode === 'all' || !e.target.closest('.menu_popup')) remove_class(el, 'active');
        })
    },

    // modal

    modal_show: (width, content) => {
        // progress
        if (common.modal_progress) return false;
        // width
        let display_width = w_width();
        if (width > display_width - 20) width = display_width - 40;
        // active
        add_class('modal', 'active');
        common.modal_open = true;
        set_style('modal_content', 'width', width);
        set_style(document.body, 'overflowY', 'hidden');
        // actions
        html('modal_content', content);
        common.modal_resize();
    },

    modal_hide: () => {
        // progress
        if (common.modal_progress) return false;
        common.modal_progress = true;
        // update
        set_style('modal_container', 'overflow', 'hidden');
        remove_class('modal', 'active');
        html('modal_content', '');
        set_style('modal_container', 'overflow', '');
        set_style(document.body, 'overflowY', 'scroll');
        common.modal_progress = false;
        common.modal_open = false;
    },

    modal_resize: () => {
        // vars
        let h_display = window.innerHeight;
        let h_content = ge('modal_content').clientHeight;
        let k = (h_content * 100 / h_display > 85) ? 0.5 : 0.25;
        let margin = (h_display - h_content) * k;
        if (margin < 20) margin = 20;
        // update
        ge('modal_content').style.marginTop = margin + 'px';
        ge('modal_content').style.height = 'auto';
    },

    auto_hide_modal: (e) => {
        if (!has_class('modal', 'active')) return false;
        let t = e.target || e.srcElement;
        if (t.id === 'modal_overlay') on_click('modal_close');
    },

    // multiple field

    add_item_to_multiple: (e) => {
        let hidden_field = e.target.parentElement.querySelector('input[type="hidden"]'),
            el_container = e.target.parentElement.querySelector('.selected_el_container'),
            default_option = e.target.parentElement.querySelector('.default_option_el'),
            el = document.createElement('i');
        
        if (hidden_field.value) {
            hidden_field.value += ',' + e.target.value;
        } else {
            hidden_field.value = e.target.value;
        }

        el_container.querySelector('.no_selected').classList.add('not_active');
        el.dataset.value = e.target.value;
        el.innerHTML = e.target.options[e.target.selectedIndex].classList.add('not_active');
        el.innerHTML = e.target.options[e.target.selectedIndex].innerHTML;
        el_container.append(el);
        default_option.selected = true;

        el.addEventListener('click', function(event) {
            common.remove_item_from_multiple(event);
        })
    },

    remove_item_from_multiple: (e) => {
        let parent = e.target.parentElement,
            field_container = e.target.closest('.multiple_field'),
            hidden_field = field_container.querySelector('input[type="hidden"]'),
            value_arr = hidden_field.value.split(','),
            value_arr_index = value_arr.indexOf(e.target.dataset.value),
            option_el = field_container.querySelector('option[value="' + e.target.dataset.value + '"]');

        option_el.classList.remove('not_active');
        e.target.remove();

        if (value_arr_index >= 0) {
            value_arr.splice(value_arr_index, 1);
            hidden_field.value = value_arr.join(',');
        }

        if (parent.children.length <= 1) {
            parent.querySelector('.no_selected').classList.remove('not_active');
        }
    },

    // auth

    auth_send: () => {
        // vars
        let data = {phone: gv('phone')};
        let location = {dpt: 'auth', act: 'send'};
        // call
        request({location: location, data: data}, (result) => {
            if (result.error_msg) {
                html('login_note', result.error_msg);
                remove_class('login_note', 'fade');
                setTimeout(function() { add_class('login_note', 'fade'); }, 3000);
                setTimeout(function() { html('login_note', ''); }, 3500);
            } else html(qs('body'), result.html);
        });
    },

    auth_confirm: () => {
        // vars
        let data = { phone: gv('phone'), code: gv('code') };
        let location = { dpt: 'auth', act: 'confirm' };
        // call
        request({ location: location, data: data }, (result) => {
            if (result.error_msg) {
                html('login_note', result.error_msg);
                remove_class('login_note', 'fade');
                setTimeout(function() { add_class('login_note', 'fade'); }, 3000);
                setTimeout(function() { html('login_note', ''); }, 3500);
            } else window.location = window.location.href;
        });
    },

    // search

    search_do: (act) => {
        // vars
        let data = { search: gv('search') };
        let location = { dpt: 'search', act: act };
        // call
        request({location: location, data: data}, (result) => {
            html('table', result.html);
            html('paginator', result.paginator);
        });
    },

    // plots

    plot_edit_window: (plot_id, e) => {
        // actions
        cancel_event(e);
        common.menu_popup_hide_all('all');
        // vars
        let data = {plot_id: plot_id};
        let location = {dpt: 'plot', act: 'edit_window'};
        // call
        request({location: location, data: data}, (result) => {
            common.modal_show(400, result.html);
        });
    },

    plot_edit_update: (plot_id = 0) => {
        // vars
        let data = {
            plot_id: plot_id,
            status: gv('status'),
            billing: gv('billing'),
            number: gv('number'),
            size: gv('size'),
            price: gv('price'),
            offset: global.offset
        };
        let location = {dpt: 'plot', act: 'edit_update'};
        // call
        request({location: location, data: data}, (result) => {
            common.modal_hide();
            html('table', result.html);
        });
    },

    // users

    user_edit_window: (user_id, e) => {
        // actions
        cancel_event(e);
        common.menu_popup_hide_all('all');
        // vars
        let data = {user_id: user_id};
        let location = {dpt: 'user', act: 'edit_window'};
        // call
        request({location: location, data: data}, (result) => {
            common.modal_show(400, result.html);
        });
    },

    user_edit_update: (user_id = 0) => {
        // vars
        let data = {
            user_id: user_id,
            first_name: gv('first_name'),
            last_name: gv('last_name'),
            phone: gv('phone'),
            email: gv('email'),
            plot_ids: gv('plot_ids'),
            offset: global.offset
        };
        let location = {dpt: 'user', act: 'edit_update'};
        // call
        request({location: location, data: data}, (result) => {
            if (result.errors) {
                common.set_errors(result.errors);
            } else {
                common.modal_hide();
                html('table', result.html);
            }
        });
    },

    user_delete: (user_id, e) => {
        // actions
        cancel_event(e);
        // vars
        let data = {
            user_id: user_id,
            offset: global.offset
        };
        let location = {dpt: 'user', act: 'delete'};
        // call
        request({location: location, data: data}, (result) => {
            html('table', result.html);
        });
    },

    // fields error processing

    set_errors: (errors) => {
        for (let key in errors) {
            let field = document.querySelector('#' + key);
            console.log(field)
            if (field) {
                let error_el = field.parentElement.querySelector('.error_el');
                field.classList.add('error_field');
                error_el.innerHTML = errors[key];
                error_el.classList.add('error_el_active');
            }
        }
    },

    remove_error: (e) => {
        if (!e.target.classList.contains('error_field')) {
            return;
        }

        let error_el = e.target.parentElement.querySelector('.error_el_active');

        if (error_el) {
            error_el.innerHTML = '';
            error_el.classList.remove('error_el_active')
        }

        e.target.classList.remove('error_field')
    }
}

add_event(document, 'DOMContentLoaded', common.init);