<?php
echo $this->template->load_view('header');
echo $this->template->load_view('sidebar');
echo $this->template->load_view('topbar');

echo isset($template['body']) ? $template['body'] : '';

echo $this->template->load_view('footer');
