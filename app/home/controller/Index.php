<?php
declare (strict_types = 1);

namespace app\home\controller;

class Index
{
    public function index()
    {
        return view();
    }
    public function abc(){
        $data = [
            [
                "title" =>  "333",
                "url"   =>  "/"
            ],
            [
                "title" =>  "2222",
                "url"   =>  "/"
            ],
            [
                "title" =>  "2222",
                "url"   =>  "/"
            ],
        ];
        return json_encode($data);
    }
}
