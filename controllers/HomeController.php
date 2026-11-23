<?php


/**
 * S1 - Home page of the customer front office. The page shows the blocks that modules register for the
 * "home" slot (featured shops, new products, ads...), in module order, so each module fills its own part.
 */
final class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('front/home', ['title' => 'SKYDROP | Local shops, delivered']);
    }
}
