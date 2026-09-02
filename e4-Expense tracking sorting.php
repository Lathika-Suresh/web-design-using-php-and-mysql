<?php
mysqli_report(MYSQLI_REPORT_OFF);

$conn=mysqli_connect("localhost","root","","cute_expense_tracker");

if(!$conn) die("Database Connection Failed");

/* =========================
ADD EXPENSE
========================= */

if(isset($_POST["add"])){

$item=mysqli_real_escape_string($conn,$_POST["item"]);
$amount=(float)$_POST["amount"];
$category=mysqli_real_escape_string($conn,$_POST["category"]);
$mood=mysqli_real_escape_string($conn,$_POST["mood"]);
$note=mysqli_real_escape_string($conn,$_POST["note"]);
$date=$_POST["date"];
$time=$_POST["time"];

mysqli_query($conn,"
INSERT INTO expenses
(item,amount,category,mood,note,expense_date,expense_time)
VALUES
('$item',$amount,'$category','$mood','$note','$date','$time')
");

header("Location: ".$_SERVER['PHP_SELF']);
exit();
}

/* =========================
DELETE
========================= */

if(isset($_POST["delete"])){

$id=(int)$_POST["delete"];

mysqli_query($conn,"DELETE FROM expenses WHERE id=$id");

header("Location: ".$_SERVER['PHP_SELF']);
exit();
}

/* =========================
FILTER
========================= */

$filter=isset($_GET["date"])?$_GET["date"]:"";

$where=$filter ? "WHERE expense_date='$filter'" : "";

$res=mysqli_query($conn,"
SELECT * FROM expenses
$where
ORDER BY expense_date DESC,expense_time DESC
");

$rows=[];

while($row=mysqli_fetch_assoc($res)){
$rows[]=$row;
}

/* =========================
STATS
========================= */

$total=array_sum(array_column($rows,"amount"));
$count=count($rows);
$avg=$count?($total/$count):0;

$monthly=mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(amount) total
FROM expenses
WHERE MONTH(expense_date)=MONTH(CURDATE())
AND YEAR(expense_date)=YEAR(CURDATE())
"));

$monthTotal=$monthly["total"]??0;

/* =========================
CATEGORY TOTALS
========================= */

$cats=[
"Food"=>0,
"Shopping"=>0,
"Travel"=>0,
"Health"=>0,
"Other"=>0
];

foreach($rows as $r){
$cats[$r["category"]]+=$r["amount"];
}

$pieColors=[
"#ff8dc7",
"#b197fc",
"#60a5fa",
"#34d399",
"#fbbf24"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Expense Tracker</title>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

:root{
--pink:#ff8dc7;
--purple:#b197fc;
--bg:#0f1020;
--card:#1b1d36;
--card2:#24284b;
--text:#f8f7ff;
--muted:#9ca3c7;
--border:rgba(255,255,255,.08);
--yellow:#fbbf24;
}

body{
font-family:Nunito,sans-serif;
background:var(--bg);
color:var(--text);
min-height:100vh;
background-image:
radial-gradient(circle at top left,rgba(255,141,199,.15),transparent 30%),
radial-gradient(circle at bottom right,rgba(177,151,252,.15),transparent 35%);
}

/* =========================
HEADER
========================= */

header{
padding:60px 20px 35px;
text-align:center;
border-bottom:1px solid var(--border);
}

.badge{
display:inline-flex;
padding:8px 18px;
border-radius:30px;
background:rgba(255,255,255,.05);
border:1px solid var(--border);
font-size:11px;
letter-spacing:2px;
text-transform:uppercase;
color:var(--pink);
margin-bottom:18px;
}

h1{
font-size:clamp(2.2rem,5vw,4rem);
font-family:Quicksand,sans-serif;
background:linear-gradient(135deg,#fff,var(--pink),var(--purple));
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
margin-bottom:12px;
}

header p{
color:var(--muted);
font-size:15px;
}

/* =========================
MAIN
========================= */

main{
max-width:1200px;
margin:auto;
padding:40px 20px 70px;
}

/* =========================
CARD
========================= */

.card{
background:rgba(27,29,54,.88);
border:1px solid var(--border);
border-radius:28px;
padding:30px;
margin-bottom:28px;
backdrop-filter:blur(16px);
box-shadow:0 20px 50px rgba(0,0,0,.25);
}

.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:16px;
}

label{
display:block;
font-size:11px;
letter-spacing:1.5px;
text-transform:uppercase;
color:var(--muted);
margin-bottom:7px;
}

input,select,textarea{
width:100%;
background:var(--card2);
border:1px solid var(--border);
border-radius:14px;
padding:14px 16px;
color:var(--text);
outline:none;
font-family:Nunito,sans-serif;
}

textarea{
height:60px;
resize:none;
}

input:focus,
select:focus,
textarea:focus{
border-color:var(--pink);
box-shadow:0 0 0 4px rgba(255,141,199,.12);
}

.btn{
margin-top:20px;
padding:14px 28px;
border:none;
border-radius:14px;
background:linear-gradient(135deg,var(--pink),var(--purple));
color:#fff;
font-weight:800;
cursor:pointer;
transition:.3s;
box-shadow:0 10px 25px rgba(255,141,199,.25);
}

.btn:hover{
transform:translateY(-3px);
}

/* =========================
STATS
========================= */

.stats{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:16px;
margin-bottom:28px;
}

.stat{
background:var(--card);
border:1px solid var(--border);
border-radius:22px;
padding:24px;
text-align:center;
}

.stat h2{
font-size:2rem;
background:linear-gradient(135deg,var(--pink),var(--purple));
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
}

.stat p{
margin-top:6px;
font-size:12px;
letter-spacing:2px;
text-transform:uppercase;
color:var(--muted);
}

/* =========================
REPORTS
========================= */

.report-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
gap:20px;
margin-bottom:28px;
}

.report{
background:var(--card);
border:1px solid var(--border);
border-radius:24px;
padding:24px;
}

/* =========================
PIE CHART
========================= */

.pie-wrap{
position:relative;
width:260px;
height:260px;
margin:auto;
display:flex;
align-items:center;
justify-content:center;
}

.pie{
width:100%;
height:100%;
transform:rotate(-90deg);
}

.pie circle{
fill:none;
stroke-width:3.5;
stroke-linecap:round;
transition:.4s;
}

.pie circle:hover{
stroke-width:4.5;
}

.pie-center{
position:absolute;
text-align:center;
}

.pie-total{
font-size:1.8rem;
font-weight:800;
background:linear-gradient(135deg,var(--pink),var(--purple));
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
}

.pie-center span{
font-size:12px;
color:var(--muted);
letter-spacing:2px;
text-transform:uppercase;
}

.legend{
display:flex;
flex-direction:column;
gap:12px;
margin-top:24px;
}

.legend-item{
display:flex;
justify-content:space-between;
align-items:center;
padding:12px 14px;
border-radius:14px;
background:rgba(255,255,255,.03);
}

.legend-left{
display:flex;
align-items:center;
gap:10px;
font-weight:700;
}

.dot{
width:12px;
height:12px;
border-radius:50%;
}

/* =========================
BAR GRAPH
========================= */

.cat-graph{
display:flex;
flex-direction:column;
gap:20px;
margin-top:15px;
}

.graph-row{
display:flex;
flex-direction:column;
gap:8px;
}

.graph-top{
display:flex;
justify-content:space-between;
font-size:14px;
font-weight:700;
}

.graph-bg{
height:18px;
border-radius:30px;
background:rgba(255,255,255,.05);
overflow:hidden;
}

.graph-fill{
height:100%;
border-radius:30px;
transition:.5s;
box-shadow:0 6px 18px rgba(255,141,199,.25);
}

.graph-fill:hover{
transform:scaleY(1.08);
filter:brightness(1.1);
}

.graph-percent{
font-size:12px;
color:var(--muted);
text-align:right;
}

/* =========================
FILTER
========================= */

.filter{
display:flex;
justify-content:space-between;
align-items:center;
gap:14px;
flex-wrap:wrap;
margin-bottom:24px;
}

.filter form{
display:flex;
gap:12px;
flex-wrap:wrap;
}

/* =========================
TABLE
========================= */

.table{
overflow:auto;
}

table{
width:100%;
border-collapse:collapse;
}

thead{
background:rgba(255,255,255,.03);
}

thead th{
padding:16px 20px;
text-align:left;
font-size:11px;
letter-spacing:2px;
text-transform:uppercase;
color:var(--muted);
}

tbody tr{
border-top:1px solid rgba(255,255,255,.04);
}

tbody tr:hover{
background:rgba(255,255,255,.03);
}

td{
padding:18px 20px;
}

.item{
font-weight:800;
}

.note{
font-size:12px;
color:var(--muted);
margin-top:4px;
}

.amount{
font-weight:800;
color:var(--yellow);
}

.tag{
display:inline-block;
padding:5px 12px;
border-radius:20px;
font-size:11px;
font-weight:800;
background:rgba(255,255,255,.08);
}

.time{
font-size:12px;
color:var(--muted);
margin-top:3px;
}

.del{
padding:8px 14px;
border:none;
border-radius:10px;
background:rgba(255,80,120,.12);
color:#ff7aa2;
font-weight:700;
cursor:pointer;
}

.empty{
padding:50px;
text-align:center;
color:var(--muted);
}

footer{
text-align:center;
padding:26px;
border-top:1px solid var(--border);
color:var(--muted);
font-size:12px;
margin-top:60px;
}

</style>
</head>

<body>

<header>

<div class="badge">
♡ Smart Finance Diary
</div>

<h1>
Expense Tracker
</h1>

<p>
Track spending, analyze habits and revisit your previous financial memories ✨
</p>

</header>

<main>

<!-- ADD EXPENSE -->

<div class="card">

<h2 style="margin-bottom:22px">
✨ Add Expense
</h2>

<form method="POST">

<div class="grid">

<div>
<label>Expense</label>
<input type="text" name="item" placeholder="Bubble Tea" required>
</div>

<div>
<label>Amount ₹</label>
<input type="number" name="amount" step="0.01" required>
</div>

<div>
<label>Category</label>
<select name="category">
<option>Food</option>
<option>Shopping</option>
<option>Travel</option>
<option>Health</option>
<option>Other</option>
</select>
</div>

<div>
<label>Mood</label>
<select name="mood">
<option>😊</option>
<option>😍</option>
<option>😭</option>
<option>🥲</option>
<option>😎</option>
</select>
</div>

<div>
<label>Date</label>
<input type="date" name="date" value="<?= date("Y-m-d") ?>" required>
</div>

<div>
<label>Time</label>
<input type="time" name="time" value="<?= date("H:i") ?>" required>
</div>

<div style="grid-column:1/-1">
<label>Little Note</label>
<textarea name="note" placeholder="Bought cute snacks 🌸"></textarea>
</div>

</div>

<button class="btn" type="submit" name="add">
+ Add Expense
</button>

</form>

</div>

<!-- STATS -->

<div class="stats">

<div class="stat">
<h2>₹<?= number_format($total,2) ?></h2>
<p>Total Spending</p>
</div>

<div class="stat">
<h2><?= $count ?></h2>
<p>Total Expenses</p>
</div>

<div class="stat">
<h2>₹<?= number_format($avg,2) ?></h2>
<p>Average Spend</p>
</div>

<div class="stat">
<h2>₹<?= number_format($monthTotal,2) ?></h2>
<p>This Month</p>
</div>

</div>

<!-- REPORTS -->

<div class="report-grid">

<!-- PIE CHART -->

<div class="report">

<h3 style="margin-bottom:22px">
💖 Spending Distribution
</h3>

<?php
$totalPie=array_sum($cats);
$start=0;
?>

<div class="pie-wrap">

<svg viewBox="0 0 36 36" class="pie">

<?php
$i=0;

foreach($cats as $k=>$v):

$percent=$totalPie>0?($v/$totalPie)*100:0;
$dash=$percent;
$offset=100-$start;
?>

<circle
cx="18"
cy="18"
r="15.915"
stroke="<?= $pieColors[$i] ?>"
stroke-dasharray="<?= $dash ?> 100"
stroke-dashoffset="<?= $offset ?>"
></circle>

<?php
$start+=$dash;
$i++;
endforeach;
?>

</svg>

<div class="pie-center">

<div class="pie-total">
₹<?= number_format($total,0) ?>
</div>

<span>Total</span>

</div>

</div>

<div class="legend">

<?php
$i=0;
foreach($cats as $k=>$v):
?>

<div class="legend-item">

<div class="legend-left">

<div class="dot"
style="background:<?= $pieColors[$i] ?>">
</div>

<?= $k ?>

</div>

<div>
₹<?= number_format($v,0) ?>
</div>

</div>

<?php
$i++;
endforeach;
?>

</div>

</div>

<!-- BAR GRAPH -->

<div class="report">

<h3 style="margin-bottom:22px">
📊 Category Analytics
</h3>

<div class="cat-graph">

<?php
$i=0;

foreach($cats as $k=>$v):

$p=$total>0?($v/$total)*100:0;
?>

<div class="graph-row">

<div class="graph-top">

<span>
<?= $k ?>
</span>

<span>
₹<?= number_format($v,2) ?>
</span>

</div>

<div class="graph-bg">

<div class="graph-fill"
style="
width:<?= max($p,2) ?>%;
background:
linear-gradient(
90deg,
<?= $pieColors[$i] ?>,
<?= $pieColors[$i] ?>aa
)">
</div>

</div>

<div class="graph-percent">
<?= round($p,1) ?>%
</div>

</div>

<?php
$i++;
endforeach;
?>

</div>

</div>

</div>

<!-- FILTER -->

<div class="card filter">

<div>

<h3 style="margin-bottom:5px">
📅 Expense Calendar
</h3>

<div style="font-size:13px;color:var(--muted)">
Track previous expenses by date
</div>

</div>

<form>

<input type="date" name="date" value="<?= htmlspecialchars($filter) ?>">

<button class="btn" style="margin:0;padding:12px 20px">
Track
</button>

<a href="<?= $_SERVER['PHP_SELF'] ?>" style="
padding:12px 18px;
border-radius:12px;
background:rgba(255,255,255,.05);
border:1px solid var(--border);
color:var(--text);
text-decoration:none;
font-weight:700">
Reset
</a>

</form>

</div>

<!-- TABLE -->

<div class="card table">

<h2 style="margin-bottom:22px">
🧾 Expense History
</h2>

<table>

<thead>

<tr>
<th>Expense</th>
<th>Category</th>
<th>Mood</th>
<th>Date & Time</th>
<th>Amount</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php if(empty($rows)): ?>

<tr>
<td colspan="6" class="empty">
🌸 No expenses found
</td>
</tr>

<?php else: foreach($rows as $row): ?>

<tr>

<td>

<div class="item">
<?= htmlspecialchars($row["item"]) ?>
</div>

<?php if(!empty($row["note"])): ?>

<div class="note">
<?= htmlspecialchars($row["note"]) ?>
</div>

<?php endif; ?>

</td>

<td>

<span class="tag">
<?= htmlspecialchars($row["category"]) ?>
</span>

</td>

<td style="font-size:20px">
<?= htmlspecialchars($row["mood"]) ?>
</td>

<td>

<div>
<?= date("d M Y",strtotime($row["expense_date"])) ?>
</div>

<div class="time">
<?= date("h:i A",strtotime($row["expense_time"])) ?>
</div>

</td>

<td class="amount">
₹<?= number_format($row["amount"],2) ?>
</td>

<td>

<form method="POST">

<input type="hidden"
name="delete"
value="<?= $row["id"] ?>">

<button class="del">
Delete
</button>

</form>

</td>

</tr>

<?php endforeach; endif; ?>

</tbody>

</table>

</div>

</main>

<footer>
Cute Expense Tracker © 2026 · Smart Finance Diary · Made with ♡
</footer>

</body>
</html>