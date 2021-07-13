<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use stdClass;
use DB;

class IndexController extends Controller
{
    public function index(){
        $data_url_array = array();
        for($i = 2;$i <= 5;$i++){
            $page_url = 'http://www.fj.xinhuanet.com/jinrong/index_'.$i.'.htm';
            //判断分页url返回的状态码
            $res = $this->get_url_status_code($page_url);
            $data = '';
            if($res[0]['code'] == 200){
                //状态码ok就获取新闻详情页地址
                $data = $this->get_url($res[0]['data']);
            }else{
                $data = '';
            }
            array_push($data_url_array,$data);
        }
        //所有的子页面存储在data_url_array 中43

        $new_data[][] = new stdClass();
        $flag = 0;
        foreach ($data_url_array as $key => $value){
            foreach ($value as $k => $v){
                $url = $v;
                $res = $this->get_url_status_code($url);
                $data = $this->get_detail($res[0]['data']);
                $title = $data[0]['title'];
                $content = '';
                foreach ($data[0]['content'] as $k => $v){
                    $content .= $v;
                }
                //写入新数组
                echo '写入第'.$flag.'条数据:'.$title.'<br/><hr>';
//                DB::table('xinhua')->insert([
//                    'title' => $title,
//                    'content' => $content
//                ]);
//                    $new_data[$flag]['title']= $title;
//                    $new_data[$flag]['content'] = $content;
                $flag++;
            }
        }
    }
    //获取分页页面的所有文章url
    public function get_url($data){
        $body = '';//body中的内容<div class="list">
        $rule = "/<body[^>]*?>(.*\s*?)<\/body>/is";
        preg_match($rule,$data,$body);
        $rule = "/<div class=".'"list"'."[^>]*?>(.*\s*?)<\/div>/is";
        preg_match($rule,$body[0],$div);
        $rule = "/<div class=".'"list"'."[^>]*?>(.*\s*?)<\/div>/is";
        preg_match($rule,$body[0],$div);
        $rule = "/<li[^>]*?>(.*\s*?)<\/li>/is";
        preg_match($rule,$div[0],$li);
        $rule = "/<a[^>]*?>(.*\s*?)<\/a>/is";
        preg_match($rule,$li[0],$a);
        $rule='/http([^\"]+)/';
        preg_match_all($rule,$a[0],$href);
        return $href[0];
    }
    public function get_url_status_code($page_url){
        $client = new Client();
        $res = $client->request('GET', $page_url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'query' => [ //参数
                'page' => 1,
                'size' => '10'
            ],
            'timeout' => 20, //超时时间（秒）
        ]);
        $status_code = $res->getStatusCode(); // 获得接口反馈状态码
        if($status_code == 200){
            $body = $res->getBody(); //获得接口返回的主体对象
            $data = $body->getContents(); //获得主体内容
            return array([
                'code' => $status_code,
                'data' => $data
            ]);
        } else {
            return array([
                'code' => 'error',
                'data' => ''
            ]);
        }
    }
    public function get_detail($data){
        $rule = "/<title[^>]*?>(.*\s*?)<\/title>/is";
        preg_match($rule,$data,$title);//获取标题
        $title = $title[1];
        $rule = "/<div id=".'"Content"'."[^>]*?>(.*\s*?)<\/div>/is";
        preg_match_all($rule,$data,$content);
        if($content[0] == NULL){
            $rule = "/<div id=".'"detail"'."[^>]*?>(.*\s*?)<\/div>/is";
            preg_match_all($rule,$data,$content);
        }
        if($content[0] == NULL){
            $rule = "/<div id=".'"p-detail"'."[^>]*?>(.*\s*?)<\/div>/is";
            preg_match_all($rule,$data,$content);
        }
        $rule = "/<p>(.*?)<\/p>/";
        preg_match_all($rule,$content[0][0],$p);
        $content = $p[0];
        array_pop($content);
        return array([
            'title' =>  $title,
            'content' => $content
        ]);
    }
}
