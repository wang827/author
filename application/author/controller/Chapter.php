<?php
namespace app\author\controller;
use think\Controller;
use think\Db;
use think\Request;
class Chapter extends Base{
    public function index($bookid){

        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
        $shell= $this->shell($bookid);
        if($shell===false){
            $this->error('您不是该书的作者，没有权限操作');
        }
       $book=Db::name('Book')->where(['book_id'=>$bookid])->find();
        $chapter=$this->chapter($bookid);//获取章节列表

        $t_juan=$this->total_juan($bookid);
        $t_list=$this->total_list($bookid);
        $volume_id=$this->max_juan($bookid);
        $content=$this->content($bookid);//获取第一章节内容
      // print_r($content);exit();

        return $this->fetch('',[
            'book'    =>$book,
            'chapter'  =>$chapter,
            't_juan'  =>$t_juan,
            't_list'  =>$t_list,
            'content'  =>$content,
            'bookid'   =>$bookid,
            'time'     =>date('Y-m-d H:i:s'),
            'v_id'    =>$volume_id
        ]);
    }

    public function add($bookid){


       if(!is_numeric($bookid)){
           $this->error('参数错误');
       }
        $shell= $this->shell($bookid);
        if($shell===false){
            $this->error('您不是该书的作者，没有权限操作');
        }
        $this->createToken();
      $book=Db::name('Book')->where(['book_id'=>$bookid])->find();
        $juan =$this->get_juan($bookid);
        $volume_id=$this->max_juan($bookid);
        $maxJuan =$this->getMaxJuan($bookid);

      $price=  Db::name('Content')->where(['book_id'=>$bookid,'type'=>0])->order('num desc')->find();
      if(is_array($price)){
          if($price['price']==0){
              $str="免费";
          }elseif ($price['price']==3){
              $str="千字3分";
          }elseif ($price['price']==4){
              $str="千字4分";
          }elseif ($price['price']==5){
              $str="千字5分(书咚建议价格)";
          }elseif ($price['price']==6){
              $str="千字6分";
          }elseif ($price['price']==8){
              $str="千字8分";
          }elseif ($price['price']==10){
              $str="千字10分";
          }elseif ($price['price']==12){
              $str="千字12分";
          }elseif ($price['price']==14){
              $str="千字14分";
          }elseif ($price['price']==16){
              $str="千字16分";
          }elseif ($price['price']==18){
              $str="千字18分";
          }
      }else{
          $str="免费";
      }
       // echo $volume_id;
       return $this->fetch('',[
           'book'    =>$book,
           'juan'    =>$juan,
           'v_id'   =>$volume_id,
           'maxJuan'  =>$maxJuan,
           'price'   =>$str
       ]);

    }
  //获取最大卷
    public function getMaxJuan($bookid){
      $juan=  Db::name('Content')->where(['book_id'=>$bookid,'type'=>1])->order('volume_id desc')->find();
       return $juan['title'];
    }
    //书籍章节
    public function chapter($bookid){
        $chapter=Db::name('Content')->field('volume_id,title')->where(['book_id'=>$bookid,'type'=>1])->select();
        $count=count($chapter);
        for ($i=0;$i<$count;$i++){
            $chapter[$i]['chapter']=Db::name('Content')->field('content_id,book_id,title,number,the_price,time')->where(['book_id'=>$bookid,'type'=>0,'volume_fid'=>$chapter[$i]['volume_id'],'state'=>['neq',3]])->select();
            $chapter[$i]['count']=Db::name('Content')->field('content_id')->where(['book_id'=>$bookid,'type'=>0,'volume_fid'=>$chapter[$i]['volume_id'],'state'=>['neq',3]])->count();
        }
        return $chapter;
    }
    //总卷数
    private function total_juan($bookid){
     return   $chapter=Db::name('Content')->field('volume_id')->where(['book_id'=>$bookid,'type'=>1])->count();

    }
    //当前最大卷数
    private function max_juan($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
        $juan =Db::name('Content')->where(['book_id'=>$bookid,'type'=>1])->field('volume_id')->order('volume_id desc')->find();
        return $juan['volume_id'];
    }
    //总章节数
    private function total_list($bookid){
        return   $chapter=Db::name('Content')->field('volume_id')->where(['book_id'=>$bookid,'type'=>0,'state'=>['neq',3]])->count();

    }

    //默认第一章节内容
    public function content($bookid){

        $content=Db::view('Content','content_id,title,number,num,the_price,time,update_time')
                   ->view('Contents','content,msg','Contents.content_id=Content.content_id')
                   ->where(['Content.book_id'=>$bookid,'type'=>0,'Content.state'=>['neq',3]])
                   ->order('Content.num ASC')
                   ->find();
        $content['content']=html_entity_decode($content['content']);
        $content['content']=str_replace("\n","</p><p style=\"text-indent: 2em;line-height: 40px;font-size: 16px;\">",$content['content']);
        return $content;
    }
    //创建TOKEN
   public function createToken() {

        $code =md5(time());
        session('TOKEN',$code);
    }
    //判断TOKEN
    function checkToken($token) {
        if ($token == session('TOKEN')) {
            session('TOKEN', NULL);
            return TRUE;
        } else {
            return FALSE;
        }
    }


    public function addChapter(){


        if(!request()->isPost()){
            $this->error('系统错误');
        }
        $data=input('post.');

        if(!$this->checkToken($data['token'])){

            $this->error('请不要重复提交页面');
        }
      $chong=  Db::name('Content')->where(['book_id'=>$data['bookid'],'title'=>trim($data['chapter'])])->find();
        if(count($chong)!=0){

            $this->error('请不要重复发布章节');
            exit();
        }

//        $chong1=  Db::name('Contents')->where(['content'=>$data['txt']])->find();
//        if(count($chong1)!=0){
//
//            $this->error('请不要重复发布章节');
//            exit();
//        }


      //  print_r($data);exit();
     if($data['chap']==1){
         if($data['roll']==""){
             $this->error('请选择分卷名');
         }
         if($data['chapter']==""){
             $this->error('章节标题不能为空');
         }
         if($data['txt']==""){
             $this->error('章节内容不能为空');
         }
         $words =$data['number'];
         if($words<100 || $words>20000){
             $this->error('章节字数在100-20000字之间');
         }
     //过滤敏感词

         $m= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['txt']);
        if($m){
             $this->error('请修改敏感词：'.$m);
        }

         $n= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['msg']);
         if($n){
             $this->error('请修改作者有话说敏感词：'.$n);
         }
         $p= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['chapter']);
         if($p){
             $this->error('请修改章节标题敏感词：'.$p);
         }

         if($data['price']=='免费'){
             $data['price']=0;
         }elseif ($data['price']=='千字3分'){
             $data['price']='3';
         }elseif ($data['price']=='千字4分'){
             $data['price']='4';
         }elseif ($data['price']=='千字5分(书咚建议价格)'){
             $data['price']='5';
         }elseif ($data['price']=='千字6分'){
             $data['price']='6';
         }elseif ($data['price']=='千字8分'){
             $data['price']='8';
         }elseif ($data['price']=='千字10分'){
             $data['price']='10';
         }elseif ($data['price']=='千字12分'){
             $data['price']='12';
         }elseif ($data['price']=='千字14分'){
             $data['price']='14';
         }elseif ($data['price']=='千字16分'){
             $data['price']='16';
         }elseif ($data['price']=='千字18分'){
             $data['price']='18';
         }

         $is =\think\Loader::controller('gongju/Chapterlei')->add($data,$data['bookid'],$data['price']);
         if ($is) {
             $this->success('增加成功！',url('chapter/index',['bookid'=>$data['bookid']]));
         } else {
             $this->error("增加失败！");
         }
     }
     if($data['drift']==2){
         if($data['roll']==""){
             $this->error('请选择分卷名');
         }
        if($data['chapter']==""){
            $this->error('章节标题不能为空');
        }
        if($data['txt']==""){
            $this->error('章节内容不能为空');
        }
         $words =$data['number'];
         if($words<100 || $words>20000){
             $this->error('章节字数在100-20000字之间');
         }
//过滤敏感词

         $m= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['txt']);
         if($m){
             $this->error('请修改敏感词：'.$m);
         }

         $n= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['msg']);
         if($n){
             $this->error('请修改作者有话说敏感词：'.$n);
         }
         $p= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['chapter']);
         if($p){
             $this->error('请修改章节标题敏感词：'.$p);
         }
         if($data['price']=='免费'){
             $data['price']=0;
         }elseif ($data['price']=='千字3分'){
             $data['price']='3';
         }elseif ($data['price']=='千字4分'){
             $data['price']='4';
         }elseif ($data['price']=='千字5分(书咚建议价格)'){
             $data['price']='5';
         }elseif ($data['price']=='千字6分'){
             $data['price']='6';
         }elseif ($data['price']=='千字8分'){
             $data['price']='8';
         }elseif ($data['price']=='千字10分'){
             $data['price']='10';
         }elseif ($data['price']=='千字12分'){
             $data['price']='12';
         }elseif ($data['price']=='千字14分'){
             $data['price']='14';
         }elseif ($data['price']=='千字16分'){
             $data['price']='16';
         }elseif ($data['price']=='千字18分'){
             $data['price']='18';
         }
         $juan=  Db::name('Content')->where(['book_id'=>$data['bookid'],'title'=>$data['roll']])->find();
         //根据当前卷获取当前最大章节坐标
         $zuobiao= Db::name('Content')->field('num')->where(['book_id'=>$data['bookid'],'volume_fid'=>$juan['volume_id']])->order('num desc')->find();
         if(is_array($zuobiao)){
             $myzb=$zuobiao['num']+1;
         }else{
             $myzb=$juan['num']+1;
         }

         //更新坐标
         $map['book_id']=$data['bookid'];
         $map['num']    =array('EGT', $myzb); //查找比该坐标大的数据
         $newzb=Db::name('Content')->where($map)->field('content_id')->order('num asc')->select();
         if(is_array($newzb)){
             $xinzb=$myzb+1;
             for ($i=0;$i<count($newzb);$i++){
                 Db::name('Content')->where(['content_id'=>$newzb[$i]['content_id']])->update(['num'=>$xinzb]);
                 $xinzb++;
             }
         }
         $con['book_id']      =$data['bookid'];
         $con['volume_fid']   =$juan['volume_id'];
         $con['num']          =$myzb;
         $con['title']        =$data['chapter'];
         $con['number'] = $words;
         if($con['number']<1000){
             $con['the_price']=0;
         }else{
             $con['the_price']=ceil($con['number'] / 1000 * $data['price']);
         }
         $con['price']  =$data['price'];
         $con['state']   =3;
         $con['time'] = date('Y-m-d H:i:s', time());
         if($data['time']){
             $con['update_time']=$data['time'];
             $con['status']=0;
         }else{
             $con['update_time']=date('Y-m-d H:i:s');
             $con['status']=0;
         }
         try{
             Db::startTrans();//开启事务
            $re1= Db::name('Content')->insert($con);
             $content_id= Db::name('Content')->getLastInsID();
             //插入内容
             $neirongs['content_id'] = $content_id;
             $neirongs['content'] = $data['txt'];
             $neirongs['msg']   =$data['msg'];
            $re2= Db::name('Contents')->insert($neirongs);
            if($re1 && $re2){
                Db::commit();//事务提交
            }

         }catch (\Exception $e){
             Db::rollback();//回滚事务
         }
          $this->success('保存草稿成功');
     }

    }

    //新增卷
    public function addJuan(){

        if(!request()->isPost()){
            $this->error('系统错误');
        }
        $data=input('post.');
        $maxJuan =$this->max_juan($data['bookid'])+1;
        if(!is_numeric($data['newRollnum'])){
            $this->error('请输入数字');
        }
       $juan= Db::name('Content')->where(['book_id'=>$data['bookid'],'volume_id'=>$data['newRollnum']])->find();
        if(is_array($juan)){
            $this->error('该卷已存在');
        }
        $juanName =Db::name('Content')->where(['book_id'=>$data['bookid'],'title'=>$data['newRoll']])->find();
        if(is_array($juanName)){
            $this->error('该卷名已存在');
        }
        if($data['newRollnum'] > $maxJuan){
            $this->error('请按顺序添加分卷号');
        }

        //数据入库
        $con['book_id']  =$data['bookid'];
        $con['type']   =1;
        $con['volume_id']  =$data['newRollnum'];
        $con['volume_fid'] =0;
        $con['num']=$this->maxChapter($data['bookid'])+1;
        $con['title'] =$data['newRoll'];
        $con['state']  =1;
        $con['time']  =date('Y-m-d H:i:s');
        $con['update_time']  =date('Y-m-d H:i:s');
        $result=Db::name('Content')->insert($con);
        if($result){
            //更新书籍章节数
          //  $datas['chapter'] = array('exp', "chapter+1"); //总数多少章
          //  Db::name('Book')->where(array('book_id' => $data['bookid']))->update($datas);
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }
  //获取最大章节数
    private function maxChapter($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
       $num= Db::name('Content')->where(['book_id'=>$bookid])->order(['num desc'])->find();
        return $num['num'];
    }
    //修改章节
    public function save($id){
        if(!is_numeric($id)){
            $this->error('参数错误');
        }

       $bookid= Db::name('Content')->where(['content_id'=>$id])->field('book_id,num')->find();
        $shell= $this->shell($bookid['book_id']);
        if($shell===false){
            $this->error('您不是该书的作者，没有权限操作');
        }
        $book=Db::name('Book')->where(['book_id'=>$bookid['book_id']])->find();

       
        $chapter=Db::view('Content','content_id,title,price,volume_id,volume_fid')
                    ->view('Contents','content,msg','Contents.content_id=Content.content_id')
                    ->where(['content_id'=>$id])
                    ->find();
        $chapter['content']=html_entity_decode($chapter['content']);


       // $redis->set($redisKey, $chapter['content'], 86400);


        $juan=$this->juan($book['book_id'],$chapter['volume_fid']);
        $volume_id=$this->max_juan($bookid['book_id']);
        return $this->fetch('',[
            'chapter'   =>$chapter,
            'book'      =>$book,
            'juan'      =>$juan,
            'content_id'  =>$id,
            'v_id'    =>$volume_id
        ]);

    }
    //获取卷
    public function juan($bookid,$volume_fid){

       $juan= Db::name('Content')->where(['book_id'=>$bookid,'volume_id'=>$volume_fid])->field('title')->find();
       return $juan['title'];
    }
    //
    public function saveChapter(){

        if(!request()->isPost()){
            $this->error('系统错误');
        }
        $data=input('post.');
        if($data['ok']==1){
            if($data['chapter']==""){
                $this->error('章节标题不能为空');
            }
            if($data['txt']==""){
                $this->error('章节内容不能为空');
            }
            $words =$data['number'];
            if($words<100 || $words>20000){
                $this->error('章节字数在100-20000字之间');
            }
//过滤敏感词

            $m= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['txt']);
            if($m){
                $this->error('请修改敏感词：'.$m);
            }
            $n= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['msg']);
            if($n){
                $this->error('请修改作者有话说敏感词：'.$n);
            }
            $p= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['chapter']);
            if($p){
                $this->error('请修改章节标题敏感词：'.$p);
            }
            if($data['price']=='免费'){
                $data['price']=0;
            }elseif ($data['price']=='千字3分'){
                $data['price']='3';
            }elseif ($data['price']=='千字4分'){
                $data['price']='4';
            }elseif ($data['price']=='千字5分(书咚建议价格)'){
                $data['price']='5';
            }elseif ($data['price']=='千字6分'){
                $data['price']='6';
            }elseif ($data['price']=='千字8分'){
                $data['price']='8';
            }elseif ($data['price']=='千字10分'){
                $data['price']='10';
            }elseif ($data['price']=='千字12分'){
                $data['price']='12';
            }elseif ($data['price']=='千字14分'){
                $data['price']='14';
            }elseif ($data['price']=='千字16分'){
                $data['price']='16';
            }elseif ($data['price']=='千字18分'){
                $data['price']='18';
            }

            if($data['price']>0){

                $this->error('修改vip章节请联系编辑');
                exit();
            }
           // print_r($data);exit();
            $is =\think\Loader::controller('gongju/Chapterlei')->save($data);
            if($is==1){
                $this->success('章节修改成功',url('Chapter/index',['bookid'=>$data['bookid']]));
            }else{
                $this->error('章节修改失败');
            }
        }
        if($data['isok']==2){
            $this->redirect(url('Chapter/index',['bookid'=>$data['bookid']]));
        }

    }

    //多章节
    public function txt($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
        $shell= $this->shell($bookid);
        if($shell===false){
            $this->error('您不是该书的作者，没有权限操作');
        }
        if(request()->isPost()){
            $data =input('post.');
            if($data['roll']==""){
                $this->error('请选择分卷');
            }
           $volume= Db::name('Content')->field('volume_id')->where(['title'=>$data['roll']])->find();
            if($data['price']=='免费'){
                $data['price']=0;
            }elseif ($data['price']=='千字3分'){
                $data['price']='3';
            }elseif ($data['price']=='千字4分'){
                $data['price']='4';
            }elseif ($data['price']=='千字5分(书咚建议价格)'){
                $data['price']='5';
            }elseif ($data['price']=='千字6分'){
                $data['price']='6';
            }elseif ($data['price']=='千字8分'){
                $data['price']='8';
            }elseif ($data['price']=='千字10分'){
                $data['price']='10';
            }elseif ($data['price']=='千字12分'){
                $data['price']='12';
            }elseif ($data['price']=='千字14分'){
                $data['price']='14';
            }elseif ($data['price']=='千字16分'){
                $data['price']='16';
            }elseif ($data['price']=='千字18分'){
                $data['price']='18';
            }
            $file=request()->file('file');

            if($file){
                //移动到目标目录中
                $info = $file->validate(['size'=>51457280,'ext'=>'txt'])->move(ROOT_PATH . 'public' . DS . 'Upload' . DS . 'text');
                if($info){
                    //获取文件saveName
                    $name=$info->getSaveName();
                    //获取默认时间
                    $books = Db::name('Book')->where(array('book_id' => $data['bookid']))->field('chapter')->find();
                    if ($books['chapter'] == 0) {
                        $xuhao = 1;
                    } else {
                        $cons = Db::name('Content')->where(array('book_id' => $data['bookid'], 'title' => $data['roll']))->find();
                        //查找改卷下的章节
                      $myzb=  Db::name('Content')->where(['book_id'=>$data['bookid'],'volume_fid'=>$cons['volume_id']])->order('num desc')->find();
                      if(!is_array($myzb)){
                          $xuhao =$cons['num']+1;
                      }else{
                          $xuhao = $myzb['num'] + 1;
                      }

                    }

                    \think\Loader::controller('gongju/Chapterlei')->shangchuan($name, $data['bookid'], $xuhao,$volume['volume_id'],$data['price']);

                }else{
                    // 上传失败获取错误信息
                    echo $file->getError();
                }
            }

        }else{
            $book=Db::name('Book')->where(['book_id'=>$bookid])->find();//获取书籍信息
            $juan=$this->get_juan($bookid);//获取总卷数
            $volume_id=$this->max_juan($bookid);
            return $this->fetch('',[
                'book'    =>$book,
                'juan'    =>$juan,
                'bookid'  =>$bookid,
                'v_id'    =>$volume_id
            ]);
        }

    }

    //获取卷
    public function get_juan($bookid){

        $juan =Db::name('Content')->field('title')->where(['type'=>1,'book_id'=>$bookid])->select();
        return $juan;
    }

    //删除章节
    public function delete(){

         if(!request()->isPost()){
             $this->error('系统错误');
         }
         $data =input('post.');
        if(!is_numeric($data['bookid'])){
            $this->error('系统错误',$_SERVER['HTTP_REFERER'],'','2');
        }
        if($data['delreason']==""){
            $this->error('请输入删除章节的理由',$_SERVER['HTTP_REFERER'],'','2');
        }
        $arr= Db::name('BookDelete')->where(['book_id'=>$data['bookid'],'title'=>$data['delchapter'],'state'=>0])->find();
        if(count($arr)!=0){
            $this->error('该章节删除已提交，请等待处理',$_SERVER['HTTP_REFERER'],'','2');
        }
        $con['book_id']  =$data['bookid'];
        $con['title']   =$data['delchapter'];
        $con['note']   =$data['delreason'];
        $con['time']    =date('Y-m-d H:i:s');

      $result=  Db::name('BookDelete')->insert($con);
       if($result){
           $this->success('提交成功，请等待后台处理');
       }else{
           $this->error('提交失败');
       }
    }

    //草稿箱
    public function draft($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
        $shell= $this->shell($bookid);
        if($shell===false){
            $this->error('您不是该书的作者，没有权限操作');
        }

        $book =Db::name('Book')->where(['book_id'=>$bookid])->find();

        $juan_count =$this->get_draft_juan($bookid);
        $chapter_count =$this->get_draft_chapter($bookid);
         $list =$this->get_draft_list($bookid);
         $content =$this->get_draft_content($bookid);
      //echo count($content);exit();
      //   print_r($content);exit();
        return $this->fetch('',[
            'book'    =>$book,
            'j_count'   =>$juan_count,
            'c_count'    =>$chapter_count,
            'chapter'   =>$list,
            'content'    =>$content
        ]);

    }
    //获取草稿箱的卷数
    private function get_draft_juan($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
       $count= Db::name('Content')->where(['book_id'=>$bookid,'type'=>0,'state'=>3])->group('volume_fid')->count();

        return $count;
    }
    //获取草稿箱的章节数
    private function get_draft_chapter($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
        $count= Db::name('Content')->where(['book_id'=>$bookid,'type'=>0,'state'=>3])->count();

        return $count;

    }

    //获取草稿箱的章节
    public function get_draft_list($bookid){
        if(!is_numeric($bookid)){
            $this->error('参数错误');
        }
        $list=[];
       $chapter=  Db::name('Content')->field('volume_fid')->where(['book_id'=>$bookid,'type'=>0,'state'=>3])->group('volume_fid')->select();
        foreach ($chapter as $k=>$v){
            $v['volume']  =Db::name('Content')->field('volume_id,title')->where(['book_id'=>$bookid,'type'=>1,'volume_id'=>$v['volume_fid']])->find();
            $list[$k]['volume_id']=$v['volume'];

        }
        foreach ($list as $k=>$v){

           $v['chapter']  =Db::name('Content')->where(['book_id'=>$bookid,'type'=>0,'state'=>3,'volume_fid'=>$v['volume_id']['volume_id']])->select();
           $v['count']   =Db::name('Content')->where(['book_id'=>$bookid,'type'=>0,'state'=>3,'volume_fid'=>$v['volume_id']['volume_id']])->count();
           $list[$k]['volume_id']['chapter'] =$v['chapter'];
           $list[$k]['volume_id']['count']  =$v['count'];
        }
        return $list;
    }
    //默认第一章节内容
    public function get_draft_content($bookid){

        $content=Db::view('Content','content_id,title,number,the_price,time,update_time')
            ->view('Contents','content,msg','Contents.content_id=Content.content_id')
            ->where(['Content.book_id'=>$bookid,'type'=>0,'Content.state'=>3])
            ->order('Content.num ASC')
            ->find();
        $content['content']=html_entity_decode($content['content']);
        $content['content']=str_replace("\n","</p><p style=\"text-indent: 2em;line-height: 40px;font-size: 16px;\">",$content['content']);
        return $content;
    }

    //修改草稿
    public function update($contentId){

         if(!is_numeric($contentId)){
             $this->error('参数错误');
         }

        $bookid= Db::name('Content')->where(['content_id'=>$contentId])->field('book_id')->find();
        $shell= $this->shell($bookid['book_id']);
        if($shell===false){
            $this->error('您不是该书的作者，没有权限操作');
        }
        $book=Db::name('Book')->where(['book_id'=>$bookid['book_id']])->find();


        $chapter=Db::view('Content','content_id,title,price,volume_id,volume_fid')
            ->view('Contents','content,msg','Contents.content_id=Content.content_id')
            ->where(['content_id'=>$contentId])
            ->find();
        $chapter['content']=html_entity_decode($chapter['content']);
        $juan=$this->juan($book['book_id'],$chapter['volume_fid']);
        $volume_id=$this->max_juan($bookid['book_id']);
        return $this->fetch('',[
            'chapter'   =>$chapter,
            'book'      =>$book,
            'juan'      =>$juan,
            'content_id'  =>$contentId,
            'v_id'    =>$volume_id
        ]);

    }

    public function readd(){
        if(!request()->isPost()){
            $this->error('系统错误');
        }
        $data =input('post.');
       if($data['ok']==1){
           if($data['chapter']==""){
               $this->error('章节标题不能为空');
           }
           if($data['txt']==""){
               $this->error('章节内容不能为空');
           }
           $words =$data['number'];
           if($words<100 || $words>20000){
               $this->error('章节字数在100-20000字之间');
           }
           //过滤敏感词

           $m= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['txt']);
           if($m){
               $this->error('请修改敏感词：'.$m);
           }
           $n= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['msg']);
           if($n){
               $this->error('请修改作者有话说敏感词：'.$n);
           }
           $p= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['chapter']);
           if($p){
               $this->error('请修改章节标题敏感词：'.$p);
           }
           if($data['price']=='免费'){
               $data['price']=0;
           }elseif ($data['price']=='千字3分'){
               $data['price']='3';
           }elseif ($data['price']=='千字4分'){
               $data['price']='4';
           }elseif ($data['price']=='千字5分(书咚建议价格)'){
               $data['price']='5';
           }elseif ($data['price']=='千字6分'){
               $data['price']='6';
           }elseif ($data['price']=='千字8分'){
               $data['price']='8';
           }elseif ($data['price']=='千字10分'){
               $data['price']='10';
           }elseif ($data['price']=='千字12分'){
               $data['price']='12';
           }elseif ($data['price']=='千字14分'){
               $data['price']='14';
           }elseif ($data['price']=='千字16分'){
               $data['price']='16';
           }elseif ($data['price']=='千字18分'){
               $data['price']='18';
           }
          // print_r($data);exit();
      $is=\think\Loader::controller('gongju/Chapterlei')->update($data);
           if($is==1){
               $this->success('章节更新成功',url('Chapter/index',['bookid'=>$data['bookid']]));
           }else{
               $this->error('章节更新失败');
           }

       }elseif ($data['ok']==2){
           if($data['chapter']==""){
               $this->error('章节标题不能为空');
           }
           if($data['txt']==""){
               $this->error('章节内容不能为空');
           }
           $words =$data['number'];
           if($words<100 || $words>20000){
               $this->error('章节字数在100-20000字之间');
           }
//过滤敏感词

           $m= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['txt']);
           if($m){
               $this->error('请修改敏感词：'.$m);
           }
           $n= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['msg']);
           if($n){
               $this->error('请修改作者有话说敏感词：'.$n);
           }
           $p= \think\Loader::controller('gongju/Chapterlei')->sensitive_words($data['chapter']);
           if($p){
               $this->error('请修改章节标题敏感词：'.$p);
           }
           if($data['price']=='免费'){
               $data['price']=0;
           }elseif ($data['price']=='千字3分'){
               $data['price']='3';
           }elseif ($data['price']=='千字4分'){
               $data['price']='4';
           }elseif ($data['price']=='千字5分(书咚建议价格)'){
               $data['price']='5';
           }elseif ($data['price']=='千字6分'){
               $data['price']='6';
           }elseif ($data['price']=='千字8分'){
               $data['price']='8';
           }elseif ($data['price']=='千字10分'){
               $data['price']='10';
           }elseif ($data['price']=='千字12分'){
               $data['price']='12';
           }elseif ($data['price']=='千字14分'){
               $data['price']='14';
           }elseif ($data['price']=='千字16分'){
               $data['price']='16';
           }elseif ($data['price']=='千字18分'){
               $data['price']='18';
           }
           $chapter =Db::name('Content')->where(['content_id'=>$data['contentId']])->find();
           $con['title']        =trim($data['chapter']);
           $con['number'] = $words;
           if($con['number']<1000){
               $con['the_price']=0;
           }else{
               $con['the_price']=ceil($con['number'] / 1000 * $data['price']);
           }
           
           if($data['time']){
               $con['update_time']=$data['time'];
               $con['status']  =1;
           }else{
               $con['update_time'] =date('Y-m-d H:i:s');
           }
     $result=  Db::name('Content')->where(['content_id'=>$data['contentId']])->update($con);
           if($result){
               $neirong['content']  =$data['txt'];
               $neirong['msg']       =$data['msg'];
               $neirong['time']     =date('Y-m-d H:i:s');
              $re= Db::name('Contents')->where(['content_id'=>$data['contentId']])->update($neirong);
              if($re){
                  $this->success('保存草稿成功');
              }else{
                  $this->error('保存草稿失败');
              }
           }else{
               $this->error('保存草稿失败');
           }

       }else{

           $this->redirect(url('Chapter/draft',['bookid'=>$data['bookid']]));
       }
    }

    //修改分卷名
    public function save_juan(){

        if(!request()->isPost()){
            $this->error('系统错误');
        }
        $data =input('post.');
        $where['book_id']  =$data['bookid'];
        $where['type']  =1;
        $where['volume_id']   =$data['newRollnum'];

        $con['title']  =$data['newRoll'];
        $con['update_time']  =date('Y-m-d H:i:s');

       $result= Db::name('Content')->where($where)->update($con);
       if($result){
           $this->success('分卷名修改成功');
       }else{
           $this->error('分卷名修改失败');
       }
    }
}