<?php

class HomeController
{
    public function index()
    {
        $featured = [];
        $latest = [];
        $bestSellers = [];
        $discounted = [];
        $mostLoved = [];
        $categories = [];
        $databaseError = null;

        try {
            $products = new ProductModel();
            $featured = $products->getFeatured();
            $latest = $products->getLatest();
            $bestSellers = $products->getBestSellers();
            $discounted = $products->getDiscounted();
            $mostLoved = $products->getMostLoved();
            $categories = $products->getCategories();
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Cửa hàng đang chuẩn bị dữ liệu. Vui lòng quay lại sau.';
        }

        $title = 'Truyện tranh chính hãng';
        $view = 'home/index';
        require PATH_VIEW . 'main.php';
    }
}