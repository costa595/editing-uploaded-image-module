<?php
// var_dump($_POST) ;

// echo "<br><br>";

// var_dump($_GET);
require_once('includes/application_top.php');
require_once('../simpleimage.php');

$create_table_page_info = tep_db_query("CREATE TABLE IF NOT EXISTS info_ripcurl_sport_pages(page_id INT(11) NOT NULL AUTO_INCREMENT, menu_name VARCHAR(32) NOT NULL, page_name VARCHAR(32) NOT NULL,category_name VARCHAR(32) NOT NULL, page_text TEXT NOT NULL, main_photo_name VARCHAR(32) NOT NULL, PRIMARY KEY (page_id))");

$create_table_images = tep_db_query("CREATE TABLE IF NOT EXISTS info_ripcurl_images(id INT(11) NOT NULL AUTO_INCREMENT, page_id INT(11) NOT NULL, photos_names VARCHAR(32) NOT NULL, PRIMARY KEY (id))");

$img_path = '../images_work/ripcurl_sport_pages/'; 

//если сначала загрузили главное фото
if((isset($_POST['image']))&&($_POST['image']>0)) $page_id = (int)$_POST['image']; 

if(isset($_GET['id'])) $page_id=(int)$_GET['id'];

if((isset($_POST['update']))&&($_POST['update']>0)) $existing_image_id = $_POST['update'];

//удаление страницы
if((isset($_GET['id'])) && ($_GET['action'] == 'delete'))
	{	
		$page_id = (int)$_GET['id'];
		$delete_page = tep_db_query('DELETE FROM info_ripcurl_sport_pages WHERE page_id = '.$page_id);
	}

//если передалась ширина выделенного изображения (из Jcrop)
if((isset($_POST['w']))&&($_POST['w']>0)){

   $x = $_POST['x'];
   $y = $_POST['y'];
   $w = $_POST['w'];
   $h = $_POST['h'];

   if(isset($_GET['id'])) $filter = $page_id;
   if(isset($page_id)) $filter = $page_id;
   //if(isset($insert_id)) $filter = $insert_id;
   	
   	$find_image_name = tep_db_fetch_array(tep_db_query("SELECT main_photo_name FROM info_ripcurl_sport_pages WHERE page_id=".$filter));

   	//загрузка вырезанного изображения
	$img_name = $find_image_name['main_photo_name'];
	$src = $img_path.$img_name;
	$img_r = imagecreatefromjpeg($src);
	$dst_r = ImageCreateTrueColor($w, $h);

	imagecopy($dst_r, $img_r,0,0,$x,$y,$w,$h);
	header('Content-type: image/jpeg');
	imagejpeg($dst_r, $src);

	if(($w>739)&&($h>230)){

		$image = new SimpleImage();
		$image->load($src);
		$image->resize(739,230);
		$image->save($src);

	}
}

//удаление определенного изображения из фотогалереи
if(isset($_POST['image_id']) && ($_POST['action'] == 'delete'))
	{
		$delete_image = tep_db_query('DELETE FROM info_ripcurl_images WHERE id = '.(int)$_POST['image_id']);
	}	

if($_GET['action']=='change'){

	//показываем данные определенной страницы
	if(isset($page_id))
	{
		$show_info = tep_db_fetch_array(tep_db_query("SELECT * FROM info_ripcurl_sport_pages WHERE page_id=".$page_id));

			$show_menu_name = $show_info['menu_name'];
			$show_page_name = $show_info['page_name'];
			$show_category_name = $show_info['category_name'];
			$show_page_text = $show_info['page_text'];
			$show_main_image = $show_info['main_photo_name'];

			$show_images = tep_db_query("SELECT * FROM  info_ripcurl_images WHERE page_id=".$page_id);
			//берем названия изображений
			while($image = tep_db_fetch_array($show_images))
				{
					$images_info['files'][$image['id']] = $image;
				}
	}

	$allowed_types =  array("jpg", "png", "jpeg");

	//загрузка добавленных/измененных данных из главной формы
	if((isset($_POST['menu_name']))||(isset($_POST['page_name']))||(isset($_POST['page_text']))||(isset($_POST['category_name'])))
	{
		$menu_name = $_POST['menu_name'];
		$page_name = $_POST['page_name'];
		$category_name = $_POST['category_name'];
		$page_text = $_POST['page_text'];

		if(isset($page_id)) $up_filter = $page_id;
		if(isset($page_id)) $up_filter = $page_id;

		//если уже занесли главное фото или данные, то дополняем таблицу с данными
		if((isset($page_id))||(isset($page_id))){
			$change_info = tep_db_query("UPDATE info_ripcurl_sport_pages SET menu_name = '".$menu_name."', page_name = '".$page_name."',category_name = '".$category_name."', page_text = '".$page_text."' WHERE page_id=".$up_filter);	
		} else {
			$insert_info = tep_db_query("INSERT INTO info_ripcurl_sport_pages(menu_name,page_name,category_name,page_text) VALUES ('".$menu_name."','".$page_name."','".$category_name."','".$page_text."')");
			$page_id  = tep_db_insert_id();//последняя запись
		}

		//инкремент таблицы с изображениями (для их названия)
		$inc = tep_db_query("SHOW TABLE STATUS LIKE 'info_ripcurl_images'");
		$id = tep_db_result($inc, 0, 'Auto_increment');

		//загрузка фотогалереи
		foreach($_FILES['photos']['name'] as $k=>$f)
			{
				$type = substr($f, 1 + strrpos($f, "."));	

				$new_img_name = "sport".$id.".".$type;
				$new_img_path = $img_path.$new_img_name;

				$small_new_img_name = "s_sport".$id.".".$type;
				$small_new_img_path = $img_path.$small_new_img_name;	

				if (in_array($type, $allowed_types)) 
					{
						if(move_uploaded_file($_FILES['photos']['tmp_name'][$k], $new_img_path))
							{
								//Уменьшение картинки
								$image = new SimpleImage();
								$image->load($new_img_path);
								$width = $image->getWidth();
								$height = $image->getHeight();
								$const = max($width/150, $height/150);
								$new_width = $width/$const;
								$new_height = $height/$const;
								$image->resize($new_width, $new_height);
								$image->save($small_new_img_path);

								//Добавление названия картинки 
								if(isset($page_id)) $up_filter = $page_id;
								if(isset($page_id)) $up_filter = $page_id;

								if(isset($up_filter))
								{
									$add_image = tep_db_query('INSERT INTO info_ripcurl_images(page_id, photos_names) VALUES("'.$up_filter.'","'.$new_img_name.'")');
								} 

							}
					} 
				$id += 1;	
			}
			
		if($_POST['get_preview'])
		{
			header("Location: ripcurl_site.php?action=preview&id=$page_id");//
			die();

		} else {

			if(isset($page_id)) header("Location: ripcurl_site.php?action=change&id=$page_id");//
			if(isset($page_id)) header("Location: ripcurl_site.php?action=change&id=$page_id");//
			die();

		}

	} 

		//Загрузка основного изображения
	$name = $_FILES['main_photo']['name'];
	if(isset($name))
	{
		$inc = tep_db_query("SHOW TABLE STATUS LIKE 'info_ripcurl_sport_pages'");
		$id = tep_db_result($inc, 0, 'Auto_increment');

		$main_type = substr($name, 1 + strrpos($name, "."));
		$new_main_img_name="main_sport".$id.".".$main_type;//
		$new_main_img_path=$img_path.$new_main_img_name;


		if(in_array($main_type, $allowed_types))
		{
			if(move_uploaded_file($_FILES['main_photo']['tmp_name'], $new_main_img_path))
			{	
				if(isset($page_id)) $up_img_filter = $page_id;
				if(isset($existing_image_id)) $up_img_filter = $existing_image_id;

				if(isset($up_img_filter))
				{	
					$add_image = tep_db_query('UPDATE info_ripcurl_sport_pages SET main_photo_name = "'.$new_main_img_name.'" WHERE page_id='.$up_img_filter);				
				} else {
					$insert_image = tep_db_query('INSERT INTO info_ripcurl_sport_pages(main_photo_name) VALUES("'.$new_main_img_name.'")');
					$page_id = tep_db_insert_id();
				}

				$image = new SimpleImage();
				$image->load($new_main_img_path);
				$width = $image->getWidth();
				$height = $image->getHeight();
				//передаем НАЗВАНИЕ и ID загруженного изображения в IFRAME
				$data = $_FILES['main_photo'];

				$res = '<script type="text/javascript">';
	    		$res .= "var data = new Object;";
	   				
	        	$res .= 'data.name = "'.$new_main_img_name.'";';
	        	$res .= 'data.width = "'.$width.'";';
	        	$res .= 'data.height = "'.$height.'";';
	        				
	        	if(isset($page_id)) $res .= 'data.id = "'.$page_id.'";';

	        	if(isset($page_id)) $res .= 'data.id = "'.$page_id.'";';//
	   					 
	   			$res .= 'window.parent.handleResponse(data);';
	   			$res .= "</script>";

	   			echo $res;
			}
		}
				
	}
require_once('common-admin-top.php');
?>
<script type="text/javascript" src="includes/javascript/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="includes/javascript/ckeditor/adapters/jquery.js"></script>
<script type="text/javascript" src="includes/javascript/Jcrop/js/jquery.Jcrop.js"></script>
<link rel="stylesheet" href="includes/javascript/Jcrop/css/jquery.Jcrop.css">


<table>
<tr>
<td>
<h1><? if(isset($page_id)){ echo "Изменение страницы"; } else { echo "Добавление страницы"; }?></h1>

<form method="POST" id="loadMain" target="hiddenFrame" enctype="multipart/form-data">
Добавить главное фото: <input type="file" name="main_photo" accept="image/jpeg,image/png,image/jpg">
<input type="hidden" id="existingImage" name="update" value="">
</form>
</td>
</tr>
<tr><td>
<? 
//показываем главное изображение
echo '<div id="photo">Главное фото:</tr></td><td><img src="../images_work/ripcurl_sport_pages/'.$show_main_image.'" id="main"></div>'; 
?>
</td></tr>
</table>
<br><br>

<form id="forma" method="POST" enctype="multipart/form-data">
<table>
<tr><td>Название  меню: <input type="text" id="menu" name="menu_name" value='<? echo $show_menu_name; ?>' placeholder="Название  меню"></tr></td>

<tr><td>Название на странице: <input type="text" id="page" name="page_name" value='<? echo $show_page_name; ?>' placeholder="Название на странице"></tr></td>
<tr><td>Название категории: <select name="category_name" class="categories">
<?
$categories = tep_db_query("SELECT * FROM info_values WHERE type='ripcurl_categories'");
while($category = tep_db_fetch_array($categories))
	{
		echo '<option value="'.$category['value'].'"'.(((isset($show_category_name))&&($show_category_name == $category['value'])) ?  " selected" : "").'>'.$category['value'].'</option>';
	}
?>
</td></tr>

<tr><td>Текст на странице: <textarea class="ckeditor" name="page_text" id="text">
<script language="JavaScript">
	$( "textarea.ckeditor" ).ckeditor();
</script>
<? 
echo $show_page_text;
?>
</textarea></tr></td>

<? 

//показываем фотогалерею			
if(!empty($images_info['files']))
	{	
		echo '<tr><td>Фотогалерея:</tr></td>';
		foreach ($images_info['files'] as $key => $value) 
			{
				echo '<tr class="row" key="'.$key.'"><td><img src="../images_work/ripcurl_sport_pages/s_'.$value['photos_names'].'"> <span class="delete_img" key="'.$key.'">Удалить</span></td></tr>';	
			}
		?>
		<script language="JavaScript">
									
			$(".delete_img").click(function()
				{
					var attr = $(this).attr("key");

					$.ajax({
						type: "POST",
						url: "ripcurl_site.php?action=change",
						data: {image_id:attr,action:"delete"}
					});
													
					$(".row[key="+attr+"]").hide();						
				})	
		</script>
		<?	

	} else {}	
?>

</table>
Фото: <ul class="list">
<li><input type="file" name="photos[]" accept="image/jpeg,image/png,image/jpg"></li>
</ul>
<br><br>

<div id="add" class="but">Добавить фото</div>
<br>
<input type="hidden" name="x" id="x1" value="">
<input type="hidden" name="y" id="y1" value="">
<input type="hidden" name="w" id="w" value="">
<input type="hidden" name="h" id="h" value="">
<input type="hidden" id="sendId" name="image" value="">

<input type="submit" class="sub" value='<? if(isset($page_id)){ echo "Изменить страницу"; } else { echo "Добавить страницу"; }?>'>
<input type="submit" class="sub" name="get_preview" value='Предпросмотр'>
</form>

<iframe id="hiddenFrame" name="hiddenFrame" style="width:0px; height:0px; border:0px;"></iframe>

<script language="JavaScript">

function handleResponse(mes) 
{
	JcropAPI = $('#main').data('Jcrop');

	if(JcropAPI != null) JcropAPI.destroy();

	var txt = '../images_work/ripcurl_sport_pages/'+mes.name+'?'+Math.random();
	$('#main').attr('src',txt);
	var message = mes.id;
	$('#sendId').val(message);
	$('#existingImage').val(message);

	if((mes.width>=739)&&(mes.height>=230))
	{
		$('#main').Jcrop({
		  onChange: showCoords,
		  onSelect: showCoords,
		  aspectRatio: 739/230,
		  minSize:[739,230],
		  setSelect:[0,0,739,230]
		});

	} else {

		$('#main').Jcrop({
		  onChange: showCoords,
		  onSelect: showCoords,
		  aspectRatio: 739/230,
		  maxSize:[mes.width,mes.height],
		  setSelect:[0,0,mes.width,mes.height]
		});
	}
}

$('#add').click(function(){
	$('.list li:last-child').after("<li><input class='image' type='file' name='photos[]'></li>");
})

function showCoords(c){

      $('#x1').val(c.x);
      $('#y1').val(c.y);
      $('#w').val(c.w);
      $('#h').val(c.h);
   };

 $('#loadMain').change(function(){
 	$(this).submit();
 })

</script>

<?

}elseif($_GET['action']=='preview'){
	header('X-XSS-Protection: 0');
			
			$search_id = (int)$_GET['id'];
			$get_content = tep_db_fetch_array(tep_db_query("SELECT * FROM info_ripcurl_sport_pages WHERE page_id=".$search_id));

			$preview_menu_name = $get_content['menu_name'];
			$preview_page_name = $get_content['page_name'];
			$preview_category_name = $get_content['category_name'];
			$preview_page_text = $get_content['page_text'];
			$preview_main_image = $get_content['main_photo_name'];

			$get_content_images = tep_db_query("SELECT * FROM  info_ripcurl_images WHERE page_id=".$search_id);
			//берем названия изображений
			while($image = tep_db_fetch_array($get_content_images))
				{
					$images['files'][$image['id']] = $image;
				}

			?>
			
			<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
	      <script type="text/javascript" src="includes/javascript/image_slider.js"></script>
	      <script type="text/javascript" src="includes/javascript/ckeditor/ckeditor.js"></script>
	      <script type="text/javascript" src="includes/javascript/ckeditor/adapters/jquery.js"></script>
	      <link rel="stylesheet" href="includes/javascript/fancybox.css" type="text/css" media="screen">
		<script type="text/javascript" src="includes/javascript/fancybox.js"></script>
		<script type="text/javascript" src="includes/javascript/mousewheel.js"></script>
		

			<link href="includes/javascript/image.css" rel="stylesheet" type="text/css" />
		    <script src="includes/javascript/image_slider.js" type="text/javascript"></script>
		    <script type="text/javascript">
		     $(document).ready(function(){
                
        $('#slider-code').tinycarousel();
        $(".fancybox").fancybox();
			    });
			</script> 
		
			<style>
			#content{
				margin:0 auto;
				width:739px;
			}

			#text{
				word-wrap: break-word;
			}

#slider-code { height: 275px; overflow:hidden; position: relative; padding: 0 0 10px;   }
#slider-code .viewport { float: left; width: 640px; height: 125px; overflow: hidden; position: relative; }
#slider-code .buttons { background:url("includes/javascript/buttons.png") no-repeat scroll 0 0 transparent; display: block; margin: 30px 10px 0 0; background-position: 0 -38px; text-indent: -999em; float: left; width: 39px; height: 37px; overflow: hidden; position: relative; }
#slider-code .next { background-position: 0 0; margin: 30px 0 0 10px; }
#slider-code .disable { visibility: hidden; }
#slider-code .overview { list-style: none; position: absolute; width: 240px; left: 0 top: 0; }
#slider-code .overview li{ float: left; margin: 0 65px 0 0; padding: 1px; height: 150px; border: 1px solid #dcdcdc; width: 150px;}

			</style>

			<div id="content" align="center">
				<? 
				echo '<p id="mainImage"><img src="'.$img_path.$preview_main_image.'"></p>'; 
				echo '<div id="text">'.$preview_page_text.'</div>';
				?>
		     
			    		<div id="slider-code">
					    <a class="buttons prev" id="left" href="#"></a>
					    <div class="viewport">
					        <ul class="overview">
					            <?
			            	if(!empty($images['files']))
							{	
								foreach ($images['files'] as $key => $value) 
									{
										echo '<li><a class="fancybox" rel="group_'.$key.'" title="'.$value['photos_names'].'" href="../images_work/ripcurl_sport_pages/'.$value['photos_names'].'"><img src="../images_work/ripcurl_sport_pages/s_'.$value['photos_names'].'" class="small"></a></li>';	
									}
							}
			    			?> 
						        </ul>
						    </div>
						    <a class="buttons next" id="right" href="#"></a>
						</div>
			</div>                  

			<?
			
			die();
} else {
require_once('common-admin-top.php')
?>

<h1>Страницы Ripcurl</h1>

<a href="news.php">Новости</a> <a href="ripcurl_script.php">Магазины</a>

<table border='1' cellspacing='0' cellpadding='5' width='100%' class='shops'>
	<thead>
		<tr>
			<th>Название в меню</th>
			<th>Название на странице</th>
			<th>Текст на странице</th>
		</tr>
	</thead>

	<tbody>
	<?
		$pages = tep_db_query("SELECT * FROM info_ripcurl_sport_pages");

		while($page = tep_db_fetch_array($pages)){
			echo '<tr class='.$page['page_id'].'> <td>'.$page['menu_name'].'</td> <td>'.$page['page_name'].'</td> <td>'.$page['page_text'].'</td> <td><a href=/admin/ripcurl_site.php?action=change&id='.$page["page_id"].'>Изменить</a> / <a href=/admin/ripcurl_site.php?action=delete&id='.$page["page_id"].'> Удалить </a> / <a href=/admin/ripcurl_site.php?action=preview&id='.$page["page_id"].'> Просмотр </a></td> </tr>';
		}
	?>
	</tbody>

</table>

<a id="addd" class="but" href=/admin/ripcurl_site.php?action=change>Добавить новую страницу</a>

<?
}
?>

<style>

	h1{
		font-size:30px;
	}

	.list{
		list-style-type: none;
	}

	.delete_img,.but{
		border:1px solid #000000;
		border-radius: 5px;
		background-color: #CCCCCC;
		width:150px;
		padding:5px;
	}

	.delete_img:hover,.but:hover{
		cursor: pointer;
	}

	#addd{
		position:relative;
		top:20px;
	}

	.sub{
		width:220px;
		padding:8px;
		background-color: #f4c430;
		border-radius:5px;
		font-size:20px;
	}

	#hiddenFrame{
		position:relative;
		left:30px;
	}

	.delete_img{
		float:right;
	}

</style>
