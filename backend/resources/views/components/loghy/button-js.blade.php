<script src="{{
    'https://api001.sns-loghy.jp/buttons/' . config('loghy.site_code')
    . '?position=' . urlencode('#loghy_button_container')
    . '&beforeURL=' . urlencode(route('auth.loghy.callback.login'))
    . '&registURL=' . urlencode(route('auth.loghy.callback.register'))
    . '&errorURL=' . urlencode(route('auth.loghy.callback.error'))
}}"></script>
