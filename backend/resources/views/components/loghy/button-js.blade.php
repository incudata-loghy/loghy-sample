<script src="{{
    'https://api001.sns-loghy.jp/buttons/' . config('loghy.site_code')
    . '?position='  . urlencode('#loghy_button_container')
    . '&beforeURL=' . urlencode($callback_url)
    . '&errorURL='  . urlencode(route('auth.loghy.callback.error'))
}}"></script>
