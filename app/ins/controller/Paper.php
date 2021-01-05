<?php
declare (strict_types = 1);

namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Basket;
use app\ins\model\PaperQuestion;
use app\ins\model\Question;
use app\ins\model\QuestionOption;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\TablePosition;
use think\Db;
use think\Request;

//试卷管理
class Paper extends Admin
{
    //试卷列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $keyword = input("get.keyword","");
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];

        $list = \app\ins\model\Paper::get_page($where,"*","sort ASC",$page,$limit);
        $list['list'] = \app\ins\model\Paper::format_list($list['list']);

        return my_json($list);
    }
    //试卷编辑，将试卷下题目全部加入到组卷栏
    public function edit(){
        $paper_id = request()->get("id",0,"int");
        $paper_row = \app\ins\model\Paper::where("id",$paper_id)->where("uid",$this->uid)->find();
        if(!$paper_row)
            return my_json([],-1,"未找到试卷数据");

        $local_question_list = PaperQuestion::where("paper_id",$paper_id)->where("parent_id",2)->order('sort','asc')->field("question_id,score,sort")->select()->toArray();
        $question_ids = array_column($local_question_list,"question_id");
        $server_question_list = Question::where("id","in",$question_ids)->orderRaw("field(id,".join(",",$question_ids).")")->select()->toArray();
        $server_question_list = array_column($server_question_list,null,"id");
        foreach($local_question_list as $key => $val)
        {
            if(isset($server_question_list[$val['question_id']]))
                $local_question_list[$key]['question_data'] = $server_question_list[$val['question_id']];
        }
        $basketData = [];
        foreach ($local_question_list as $key => $val){
            $basketData[$key] = array(
                'uid'=>$this->uid,
                'question_id'=>$val['question_id'],
                'type'=>isset($val['question_data']) ? $val['question_data']['type']:'',
                'level' =>  isset($val['question_data']) ? $val['question_data']['level']:'',
                'sort'=> $val['sort'],
                'score'=>$val['score'],
                'add_time'=>time(),
                'update_time'=>time()
            );
        }
        \think\facade\Db::startTrans();
        try {
            Basket::where("uid",$this->uid)->delete();
            $basket_model = new Basket();
            $basket_model->saveAll($basketData);

            // 提交事务
            \think\facade\Db::commit();
            return my_json([],0,"操作成功");
        } catch (\Exception $e) {
            echo $e->getMessage();
            // 回滚事务
            \think\facade\Db::rollback();
            return my_json([],-1,"操作失败");
        }
    }
    //试卷编辑保存
    public function save(){
        $paper_id = request()->post("id",0,"int");
        $paper_row = \app\ins\model\Paper::where("id",$paper_id)->where("uid",$this->uid)->find();
        if(!$paper_row)
            return my_json([],-1,"未找到试卷数据");

        validate(\app\ins\validate\Paper::class)->check(request()->post());

        $update_paper_data = [
            'title' => request()->post('title'),
            'name' => request()->post('name'),
            'is_subtitle'=>request()->post('is_subtitle'),
            'subtitle' => request()->post('subtitle'),
            'is_lock' => request()->post('is_lock'),
            'is_total_score' => request()->post('is_total_score'),
            'is_paper_info' => request()->post('is_paper_info'),
            'paper_info'=>request()->post('paper_info'),
            'is_student_info'=>request()->post('is_student_info'),
            'is_becareful' => request()->post('is_becareful'),
            'becareful'=>request()->post('becareful'),
            'is_sub_section'=>request()->post('is_sub_section'),
            'sub_section'=>request()->post('sub_section'),
            'is_question_score'=>request()->post('is_question_score'),
            'update_time'  =>  time(),
        ];
        $questions = request()->post('questions',[]);
        $insert_paper_question_data = array();
        foreach($questions as $key => $val){
            $insert_paper_question_data[] = [
                'paper_id'  =>  $paper_id,
                'parent_id' => $val['parent_id'],
                'question_id' => $val['question_id'],
                'title' => $val['title'],
                'sort' => $val['sort'],//注意：使用试卷栏中的排序值
                'score' => $val['score'],
                'add_time'=>time(),
            ];
        }

        \think\facade\Db::startTrans();
        try {
            //更新试卷数据
            \app\ins\model\Paper::update($update_paper_data,["id"   =>  $paper_id]);
            //插入试卷题目关系数据
            PaperQuestion::where("paper_id",$paper_id)->delete();
            $paper_question_model = new PaperQuestion();
            $paper_question_model->saveAll($insert_paper_question_data);
            //清空组卷栏
            Basket::where(["uid"    =>  $this->uid])->delete();

            // 提交事务
            \think\facade\Db::commit();

            return my_json([],0,"操作成功");
        } catch (\Exception $e) {
            echo $e->getMessage();
            // 回滚事务
            \think\facade\Db::rollback();

            return my_json([],-1,"操作失败");
        }
    }
    //试卷添加【默认从组卷栏中添加试卷】
    public function add(){
        $post_data = request()->post();
        validate(\app\ins\validate\Paper::class)->check($post_data);

        $questions = request()->post("questions");
        $insert_paper_question_data = [];//试卷与题目关系数据
        foreach($questions as $key => $val)
        {
            $insert_paper_question_data[] = [
                'parent_id' => $val['parent_id'],
                'question_id' => $val['question_id'],
                'title' => $val['title'],
                'sort' => $val['sort'],//注意：使用试卷栏中的排序值
                'score' => $val['score'],
                'add_time'=>time(),
            ];
        }
        $insert_paper_data = [
            'ins_id' => $this->ins_id,
            'title' => request()->post('title'),
            'name' => request()->post('name'),
            'is_subtitle'=>request()->post('is_subtitle'),
            'subtitle' => request()->post('subtitle'),
            'is_lock' => request()->post('is_lock'),
            'is_total_score' => request()->post('is_total_score'),
            'is_paper_info' => request()->post('is_paper_info'),
            'paper_info'=>request()->post('paper_info'),
            'is_student_info'=>request()->post('is_student_info'),
            'is_becareful' => request()->post('is_becareful'),
            'becareful'=>request()->post('becareful'),
            'is_sub_section'=>request()->post('is_sub_section'),
            'sub_section'=>request()->post('sub_section'),
            'is_question_score'=>request()->post('is_question_score'),
            'add_time'  =>  time(),
            'uid'   =>  $this->uid,
            'subject_id'    =>  $this->subject_id,
        ];//试卷数据

        \think\facade\Db::startTrans();
        try {
            //插入试卷数据
            $paper_model = \app\ins\model\Paper::create($insert_paper_data);
            foreach($insert_paper_question_data as $key => $val)
            {
                $insert_paper_question_data[$key]['paper_id'] = $paper_model->id;
            }
            //插入试卷题目关系数据
            $paper_question_model = new PaperQuestion();
            $paper_question_model->saveAll($insert_paper_question_data);
            //清空组卷栏
            Basket::where(["uid"    =>  $this->uid])->delete();
            // 提交事务
            \think\facade\Db::commit();

            return my_json([],0,"操作成功");
        } catch (\Exception $e) {
            // 回滚事务
            \think\facade\Db::rollback();

            return my_json([],-1,$e->getMessage());
        }
    }
    //试卷删除
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $model = new \app\ins\model\Paper();
        $batch_data = [];
        foreach($id as $i)
        {
            $batch_data[] = [
                "id"    =>  $i,
                "is_delete" => 1,
                "delete_time" => time()
            ];
        }
        $model->saveAll($batch_data);

        return my_json([],0,"删除试卷成功");
    }
    //下载答题卡
    public function downloadAnswerSheet(){
        $type = request()->get('type',1);//1普通2标准3密集
        $paper_id = request()->get('paper_id',0);
        $paper_row = \app\ins\model\Paper::where('id',$paper_id)->where('uid',$this->uid)->find();
        if(!$paper_row){
            return my_json([],-1,"未找到该试卷");
        }

        $paper_question_list = PaperQuestion::where("paper_id",$paper_id)->where('parent_id',2)->select()->toArray();
        $question_ids = array_column($paper_question_list,"question_id");
        $selectCount = Question::where("id","in",$question_ids)->where("id","in",[59,23])->count();//选择题数量
        $noSelectCount = count($question_ids) - $selectCount;//非选择题数量

        $this->createAnswerWord($paper_row,$selectCount,$noSelectCount,$type);
        exit();
    }
    /*生成答题卡*/
    protected function createAnswerWord($teacherExam,$selectCount,$noSelectCount,$type){
        $phpWord = new PhpWord();
        /*默认字体与字号*/
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10.5);
        $phpWord->setDefaultParagraphStyle(
            array(
                'spacing'    => 120,
            )
        );
        /*创建页面*/
        $section = $phpWord->addSection();
        $phpWord->addTitleStyle('h1',['size' => 19, 'color' => '000000', 'bold' => true,'name'=>'宋体'],['align'=>'center']);
        $phpWord->addTitleStyle('h2',['size' => 12, 'color' => '000000', 'name'=>'宋体'],['align'=>'left']);
        $phpWord->addTitleStyle('h3',['size' => 12, 'color' => '000000', 'bold' => true, 'name'=>'宋体'],['align'=>'left']);
        /*试卷名称*/
        $section->addTitle($teacherExam->title,'h1');
        $section->addTextBreak();
        if($type == 1){
            $section->addText("学校：____________姓名：____________班级：____________学号：____________");
            if($selectCount > 0){
                $section->addTitle("选择题（请将答案填写在各试题的答题区内）",'h2');
                $phpWord->addTableStyle('score', ['borderSize' => 6, 'borderColor' => '999999']);
                $table = $section->addTable('score');
                $count = ceil($selectCount/18);
                for ($i = 0; $i < $count; $i++){
                    $table->addRow();
                    for ($r = 1; $r <= 18; $r++) {
                        $table->addCell(500, ['valign'=>'center'])->addText($r, [],['align'=>'center']);
                    }
                    $table->addRow();
                    for ($r = 1; $r <= 18; $r++) {
                        $table->addCell(500, ['valign'=>'center']);
                    }
                }
                $section->addTextBreak();
            }
        } else if($type == 2){
            $section->addTitle("试卷类型：A",'h3');
            $section->addTitle("姓名：______________班级：______________",'h2');

            $phpWord->addTableStyle('score', ['borderSize' => 6, 'borderColor' => '999999']);
            $table = $section->addTable('score');
            $table->addRow();
            for ($r = 0; $r <= 10; $r++) {
                if($r == 0){
                    $table->addCell(1000, ['valign'=>'center'])->addText('准考证号',['align'=>'center']);
                } else {
                    $table->addCell(400, ['valign'=>'center']);
                }
            }
            $section->addTextBreak();
            $phpWord->addTableStyle('parentTableStyles', ['valign' => 'center', 'cellMarginRight' => 200]);
            $secondPrentTable = $section->addTable('parentTableStyles');
            $secondPrentTableRow = $secondPrentTable->addRow(500);
            $secondPrentTableRowCell1 = $secondPrentTableRow->addCell(1300);
            $secondPrentTableRowCell2 = $secondPrentTableRow->addCell(7500);
            $table = $secondPrentTableRowCell1->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            $table->addRow(500);
            $leftText = '缺考标记<w:br />';
            $leftText .= '       口<w:br />';
            $leftText .= '缺考标记！只能由监考老师负责用黑色字迹的签字笔填';
            $table->addCell(1300)->addText($leftText, ['size' => 8],['align'=>'left']);
            $table = $secondPrentTableRowCell2->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            $table->addRow(500);
            $rightText = '注意事项<w:br />';
            $rightText .= '1、答题前，考生先将自己的姓名、准考证号码填写清楚。<w:br />';
            $rightText .= '2、请将准考证条码粘贴在右侧的[条码粘贴处]的方框内<w:br />';
            $rightText .= '3、选择题必须使用2B铅笔填涂；非选择题必须用0.5毫米黑色字迹的签字笔填写，字体工整<w:br />';
            $rightText .= '4、请按题号顺序在各题的答题区内作答，超出范围的答案无效，在草纸、试卷上作答无效。<w:br />';
            $rightText .= '5、保持卡面清洁，不要折叠、不要弄破、弄皱，不准使用涂改液、刮纸刀。<w:br />';
            $rightText .= '6、填涂样例   正确  [■]  错误  [--][√] [×]';
            $table->addCell(7500)->addText($rightText, ['size' => 8],['align'=>'left']);
            $section->addTextBreak();

            $section->addTitle("选择题（请用2B铅笔填涂）",'h2');
            $table = $section->addTable('score');
            $count = ceil($selectCount/18);
            for ($i = 0; $i < $count; $i++){
                $table->addRow();
                for ($r = 1; $r <= 18; $r++) {
                    $table->addCell(500, ['valign'=>'center'])->addText($r, [],['align'=>'center']);
                }
                $table->addRow(2000);
                for ($r = 1; $r <= 18; $r++) {
                    $table->addCell(500, ['valign'=>'center'])->addText('[A] [B] [C] [D]', [],['align'=>'center']);
                }
            }
            $section->addTextBreak();
        } else if($type == 3){
            $section->addTitle("试卷类型：B",'h3');
            $section->addTitle("姓名：______________班级：______________",'h3');

            $phpWord->addTableStyle('score', ['borderSize' => 6, 'borderColor' => '999999']);
            $table = $section->addTable('score');
            $table->addRow();
            for ($r = 0; $r <= 10; $r++) {
                if($r == 0){
                    $table->addCell(1000, ['valign'=>'center'])->addText('准考证号',['align'=>'center']);
                } else {
                    $table->addCell(400, ['valign'=>'center']);
                }
            }
            $section->addTextBreak();
            $phpWord->addTableStyle('parentTableStyles', ['valign' => 'center', 'cellMarginRight' => 200]);
            $secondPrentTable = $section->addTable('parentTableStyles');
            $secondPrentTableRow = $secondPrentTable->addRow(500);
            $secondPrentTableRowCell1 = $secondPrentTableRow->addCell(1300);
            $secondPrentTableRowCell2 = $secondPrentTableRow->addCell(7500);
            $table = $secondPrentTableRowCell1->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            $table->addRow(500);
            $leftText = '缺考标记<w:br />';
            $leftText .= '       口<w:br />';
            $leftText .= '缺考标记！只能由监考老师负责用黑色字迹的签字笔填';
            $table->addCell(1300)->addText($leftText, ['size' => 8],['align'=>'left']);
            $table = $secondPrentTableRowCell2->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            $table->addRow(500);
            $rightText = '注意事项<w:br />';
            $rightText .= '1、答题前，考生先将自己的姓名、准考证号码填写清楚。<w:br />';
            $rightText .= '2、请将准考证条码粘贴在右侧的[条码粘贴处]的方框内<w:br />';
            $rightText .= '3、选择题必须使用2B铅笔填涂；非选择题必须用0.5毫米黑色字迹的签字笔填写，字体工整<w:br />';
            $rightText .= '4、请按题号顺序在各题的答题区内作答，超出范围的答案无效，在草纸、试卷上作答无效。<w:br />';
            $rightText .= '5、保持卡面清洁，不要折叠、不要弄破、弄皱，不准使用涂改液、刮纸刀。<w:br />';
            $rightText .= '6、填涂样例   正确  [■]  错误  [--][√] [×]';
            $table->addCell(7500)->addText($rightText, ['size' => 8],['align'=>'left']);
            $section->addTextBreak();

            $section->addTitle("选择题（请用2B铅笔填涂）",'h2');
            $phpWord->addTableStyle('score', ['borderSize' => 6, 'borderColor' => '999999']);
            $table = $section->addTable('score');
            $count = ceil($selectCount/15);
            for ($i = 0; $i < $count; $i++){
                $table->addRow(2000);
                $td = '';
                for ($r = (1+$i*5); $r <= ($i+1)*5; $r++) {
                    $td.=$r.". [A] [B] [C] [D]<w:br />";
                }
                $table->addCell(3000)->addText($td, [],['align'=>'left']);
            }
            $section->addTextBreak();
        }
        if($noSelectCount > 0){
            $section->addTitle("非选择题（请在各试题的答题区内作答）",'h2');
            $phpWord->addTableStyle('score', ['borderSize' => 6, 'borderColor' => '999999']);
            for ($i = 1; $i <= $noSelectCount; $i++){
                $table = $section->addTable('score');
                $k = $selectCount+$i;
                $table->addRow(2000);
                $table->addCell(9000)->addText($k.".答：", [],['align'=>'left']);
                $section->addTextBreak();
            }
        }
        $fileName = $teacherExam->title.".docx";
        $phpWord->save($fileName,'Word2007',true);
    }
    //试卷下载
    public function download(){
        $paper_id = input("get.paper_id");
        $paper_type = input("get.paper_type","A4");
        $ext = input("get.ext","docx");

        if(!in_array($paper_type,array('A3','A4','B4'))){
            return my_json([],-1,"请选择正确的纸张类型");
        }
        if(!in_array($ext,array('doc','docx')))
            return my_json([],-1,"请选择正确的文件格式");

        $paper_data = \app\ins\model\Paper::where("id",$paper_id)->where("uid",$this->uid)->select()->toArray();
        if(empty($paper_data))
            return my_json([],-1,"未找到试卷数据");

        $local_question_list = PaperQuestion::where("paper_id",$paper_id)->order('sort','asc')->select()->toArray();

        $question_ids = array_column($local_question_list,"question_id");
        $server_question_list = Question::where("id","in",$question_ids)->orderRaw("field(id,".join(",",$question_ids).")")->select()->toArray();
        $server_question_list = array_column($server_question_list,null,"id");
        foreach($local_question_list as $key => $val)
        {
            if(isset($server_question_list[$val['question_id']]))
                $local_question_list[$key]['question_data'] = $server_question_list[$val['question_id']];
        }
        $paper_data[0]['questions'] = $local_question_list;
        $colsNum = 1;
        if($paper_type != 'A4'){
            $colsNum = 2;
        }
        $this->createWord($paper_data,$paper_type,$colsNum,$ext);//A3,A4,A5,B4,B5
//        ZTeacherExamDownloadLog::insert(
//            array(
//                'teacher_id'=>$teacher->id,
//                'exam_id'=>$examId,
//                'exam_name'=>$teacherExam[0]['title'],
//                'add_time'=>time(),
//                'update_time'=>time()
//            )
//        );
        return my_json([],0,"成功");
    }
    /*生成word文档*/
    protected function createWord($data,$paperType='A4',$colsNum = 1,$extension='docx'){
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10.5);
        $phpWord->setDefaultParagraphStyle(
            array(
                'spacing'    => 120,
            )
        );
        /*创建页面*/
        $paper = new \PhpOffice\PhpWord\Style\Paper($paperType);
        /*是否总评分表格列总宽度*/
        $paperWidth = $paper->getWidth();
        $sectionStyle = [
            'pageSizeW' => $paper->getWidth(),
            'pageSizeH' => $paper->getHeight(),
            'colsNum'=>$colsNum
        ];
        $section = $phpWord->addSection($sectionStyle);
        /*非试题部分*/
        $this->examHeader($phpWord,$section,$data[0],$paperWidth);
        foreach ($data[0]['questions'] as $key => $val){
            if($val['parent_id'] == 0){
                $section->addText($val['title'],['size' => 14, 'color' => '000000', 'bold' => true,'name'=>'宋体'],['align'=>'center']);
            }
            /*大题评分*/
            if($data[0]['is_question_score']){
                if($val['parent_id'] == 1){
                    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999','alignment' => JcTable::START,
                        'position'=>[
                            'vertAnchor' => TablePosition::VANCHOR_TEXT,
                            'rightFromText' => 100,
                        ],
                    ]);
                    $table->addRow(500);
                    $table->addCell(900, ['valign'=>'center'])->addText('阅卷人', ['bold'=>true],['align'=>'center']);
                    $table->addCell(900)->addText("");
                    $table->addRow(500);
                    $table->addCell(900,['valign'=>'center'])->addText("得分",['bold'=>true],['align'=>'center']);
                    $table->addCell(900)->addText("");
                    $section->addTextBreak();
                    $section->addText($val['title'],['size' => 12, 'color' => '000000', 'bold' => true,'name'=>'宋体'],['align'=>'left']);
                    $section->addTextBreak();
                }
            }
            /*题目内容*/
            if($val['parent_id'] == 2){
                $textRun = $section->addTextRun();//一行文本
                $textRun->addText($val['title'].".");
                $content = strip_tags($val['question_data']['content'],'<img><p>');
                if(preg_match('/\<img/',$content)){
                    $this->examExercise($textRun,$content);
                } else {
                    $contents = $this->getNoImgContent($content);//无图片的处
                    $textRun->addText($contents);
                }
                if($val['question_data']['type'] == 59 || $val['question_data']['type'] == 23){
                    $exerciseOption = QuestionOption::where("exercises_id",$val['question_data']['id'])->select()->toArray();
                    $this->examExerciseOption($textRun,$exerciseOption,$val['question_data']['option_num']);
                }
            }
        }
        $fileName = $data[0]['title'].".".$extension;
        $phpWord->save($fileName,'Word2007',true);
    }
    /*试卷头部*/
    protected function examHeader($phpWord,$section,$data,$paperWidth){
        $phpWord->addTitleStyle('h1',['size' => 14, 'color' => '000000', 'bold' => true,'name'=>'宋体'],['align'=>'center']);
        $phpWord->addTitleStyle('h2',['size' => 13, 'color' => '000000', 'bold' => true,'name'=>'宋体'],['align'=>'center']);
        /*试卷名称*/
        $section->addTitle($data['title'],'h1');
        /*试卷别名*/
        if(!empty($data['name'])){
            $section->addTitle($data['name'], 'h2');
        }
        /*考试时间：* *分钟 满分：* *分*/
        if($data['is_paper_info'] && !empty($data['paper_info'])){
            $section->addText($data['paper_info']);
        }
        /*是否考生填写*/
        if($data['is_student_info']){
            $section->addText("学校：____________姓名：____________班级：____________学号：____________");
        }
        /*是否总评分*/
        if($data['is_total_score']){
            $totalScore = array('一','二','三','四','五','六','七','八','九','十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十');
            $count = 0;
            foreach ($data['questions'] as $key => $val){
                if($val['parent_id'] == 1){
                    $count++;
                }
            }
            $cellWidth = ceil($paperWidth/$count+1);
            $phpWord->addTableStyle('score', ['borderSize' => 6, 'borderColor' => '999999']);
            $table = $section->addTable('score');
            for ($r = 1; $r <= 2; $r++) {
                $table->addRow(500);
                for ($c = 0; $c < $count+1; $c++) {
                    if($r == 1 && $c == 0){
                        $table->addCell($cellWidth, ['valign'=>'center'])->addText('题号', ['bold'=>true],['align'=>'center']);
                    } else if($r == 1) {
                        $table->addCell($cellWidth, ['valign'=>'center'])->addText($totalScore[$c-1],[],['align'=>'center']);
                    }else if($r == 2 && $c == 0) {
                        $table->addCell($cellWidth, ['valign'=>'center'])->addText('评分', ['bold'=>true],['align'=>'center']);
                    } else {
                        $table->addCell($cellWidth)->addText();
                    }
                }
            }
        }
        /*注意事项*/
        if($data['is_becareful'] && !empty($data['becareful'])){
            $section->addTextBreak();
            $section->addText("*注意事项：");
            $beCareful = explode('<p></p>',$data['becareful']);
            foreach ($beCareful as $v){
                $section->addText($v);
            }
        }
    }
    /*试卷题目*/
    protected function examExercise($textRun,$content){
        $contentSrc = $this->getContent($content);//替换src内容替换p换行标签
        $srcArray = $this->getImgAllSrc($contentSrc);//获取所有src内容
        $contents = $this->getEndContent($contentSrc);//去除图片标签并预留分段字段+img+
        foreach ($contents as $key => $value){
            $textRun->addText($value);
            if(!empty($srcArray[$key]) && preg_match('/http/',$srcArray[$key])){
                $imgData = getimagesize($srcArray[$key]);
                $img = file_get_contents($srcArray[$key]);
                if(preg_match('/http\:\/\/latex/',$srcArray[$key])){
                    $textRun->addImage($img,array(
                        'height'        => $imgData[1]/4*3,
                        'wrappingStyle' => 'inline',
                    ));
                } else {
                    $textRun->addImage($srcArray[$key],['height'=>$imgData[1]/2,'wrappingStyle' => 'inline']);
                }
            }
        }
    }
    /*选择题选项*/
    protected function examExerciseOption($textRun,$exerciseOption,$optionNum){
        foreach ($exerciseOption as $k => $v){
            $option = strip_tags($v['option'],'<img>');
            if(preg_match('/\<img/',$v['option'])){
                $contentSrc = $this->getContent($option);//替换src内容替换p换行标签
                $srcArray = $this->getImgAllSrc($contentSrc);//获取所有src内容
                $contents = $this->getEndContent($contentSrc);//去除图片标签并预留分段字段+img+
                foreach ($contents as $key => $value){
                    $textRun->addText($value);
                    if(!empty($srcArray[$key]) && preg_match('/http/',$srcArray[$key])){
                        $imgData = getimagesize($srcArray[$key]);
                        $img = file_get_contents($srcArray[$key]);
                        if(preg_match('/http\:\/\/latex/',$srcArray[$key])){
                            $textRun->addImage($img,array(
                                'height'        => $imgData[1]/4*3,
                                'wrappingStyle' => 'inline',
                            ));
                        } else {
                            $textRun->addImage($srcArray[$key],['height'=>$imgData[1]/2,'wrappingStyle' => 'inline']);
                        }
                    }
                }
            } else {
                $textRun->addText($option);
            }
            if($optionNum == 1){
                $textRun->addText("<w:br/>");
            } else if($optionNum == 2){
                if($k == 1){
                    $textRun->addText("<w:br/>");
                }
            }
        }
    }
    /*替换src内容并去除除了img标签的所有标签*/
    protected function getContent($data){
        $data = preg_replace('/src="([^\/][^"]+?)" data-latex="([^"]+?)"/', 'src="http://latex.aictb.com/?$2"', $data);
        return preg_replace('/\/\/aictb.oss/', 'http://aictb.oss', $data);
    }
    /*获取所有src*/
    protected function getImgAllSrc($tag) {
        preg_match_all('/src\=("[^"]*")/i', $tag, $matches);
        $ret = array();
        foreach($matches[0] as $i => $v) {
            $ret[] = trim($matches[1][$i],'"');
        }
        return $ret;
    }
    /*去除图片标签并预留分段字段+img+*/
    protected function getEndContent($data){
        $data = preg_replace("/<img/i", '+img+<img', $data);
        $data = strip_tags($data,"<p>");
        $data = preg_replace('/\<\/p\>/', '<w:br/>', $data);/*替换换行标签*/
        $data = preg_replace('/\<p\>/', '', $data);/*替换换行标签*/
        $data = preg_replace('/\&nbsp\;/', '', $data);/*替换换行标签*/
        return explode('+img+',$data);
    }
    /*无图片的处理*/
    protected function getNoImgContent($data){
        $data = strip_tags($data,"<p>");
        $data = preg_replace('/\<\/p\>/', '<w:br/>', $data);/*替换换行标签*/
        $data = preg_replace('/\<p\>/', '', $data);/*替换换行标签*/
        $data = preg_replace('/\&nbsp\;/', '', $data);/*替换换行标签*/
        return $data;
    }
    /*获取contentAll*/
    protected function getContentAll($id,$content,$optionNum){
        $contentAll = $content.'<table width=100%><tr>';
        $exerciseOption = ZExercisesOption::where('exercises_id',$id)->get()->toArray();
        foreach ($exerciseOption as $k => $v){

            $contentAll.='<td>'.$v['option'].'</td>';
            if($optionNum == 2 && $k == 1) {
                $contentAll."</tr><tr>";
            }
        }
        return $contentAll."</tr></table>";
    }
}
