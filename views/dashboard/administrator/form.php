<?php
if (isset($_POST) && count($_POST) !== 0) {
  session_start();
  $_SESSION['administrator'] = $_POST;
} else {
  $table = isset($_SESSION['administrator']['table']) ? $_SESSION['administrator']['table'] : 'student';
  $columns = isset($_SESSION['administrator']['columns']) ?  explode(',', $_SESSION['administrator']['columns']) : [];
  $row = isset($_SESSION['administrator']['row']) ?  explode(',', $_SESSION['administrator']['row']) : [];
  $row = array_combine($columns, $row);
  $method = isset($_SESSION['administrator']['method']) ?  $_SESSION['administrator']['method'] : "insert";
  $db = (new Database())->connect();

  function getMeta($table)
  {
    global $db;
    try {
      $statement = $db->prepare("SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '$table'");
      $statement->execute();
    } catch (PDOException $e) {
      echo $e;
      return [];
    }

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  function getDataType($table)
  {
    global $db;
    try {
      $statement = $db->prepare("DESCRIBE `$table`");
      $statement->execute();
    } catch (PDOException $e) {
      echo $e;
      return [];
    }

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  function getReferencedRow($referenced_table)
  {
    global $db;
    try {
      $statement = $db->prepare("SELECT * FROM `$referenced_table`");
      $statement->execute();
    } catch (PDOException $e) {
      return [];
    }
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  $types = getDataType($table); //  [a, b, c]
  $meta = getMeta($table); // [d, e, f]

  $mergedArray = array_map(function ($type) {
    global $meta, $row;
    $search = array_keys(array_column($meta, "COLUMN_NAME"), $type['Field']);
    $index = end($search);
    $referenced_table = $meta[$index]['REFERENCED_TABLE_NAME'];
    return array_merge(
      array_combine(
        ["reference_table", "name", "type", "key", "value", "rows"],
        [
          $referenced_table,
          $type['Field'],
          $type['Type'],
          $type['Key'],
          $row[$type['Field']] ?? "",
          getReferencedRow($referenced_table)
        ],
      )
    );
  }, $types);
?>

  <div class="mb-4">
    <a href=".?dashboard=administrator&administrator=manage" class="btn btn-outline-primary">⬅ Back</a>
  </div>
  <form action="script/php/<?= $method; ?>Form.php" method="POST">
    <header class="mb-3">
      <h1 class="fs-3">📄 <?= ucfirst($table) ?></h1>
      <span class="text-secondary fs-6 fst-italic">Fill in the form below to insert a new data</span>
    </header>
    <hr>
    <?php foreach ($mergedArray as $column) { ?>
      <?php if (strpos($column['type'], 'varchar') !== false) { ?>
        <div class="mb-3">
          <label for="<?= $column['name'] ?>" class="form-label"><?= ucwords(join(" ", explode("_", $column['reference_table'] !== null ? $column['reference_table'] : $column['name']))) ?> </label>
          <input type="text" class="form-control" name="<?= $column['name'] ?>" value="<?= $column['value'] ?>" required>
        </div>
      <?php } ?>
      <?php if (strpos($column['type'], 'int') !== false) { ?>
        <?php if ($column['key'] === 'PRI' && $column['reference_table'] === null) { ?>
          <div class="mb-3">
            <label for="<?= $column['name'] ?>" class="form-label"><?= strtoupper(join(" ", explode("_", $column['reference_table'] ? $column['reference_table'] : $column['name']))) ?></label>
            <input type="text" readonly class="form-control" style="pointer-events: none;" name="<?= $column['name'] ?>" value="<?= $column['value'] ?>" required>
          </div>
        <?php } else if (($column['key'] === 'MUL' || $column['key'] === 'PRI') && $column['reference_table'] !== null) { ?>
          <div class="mb-3">
            <label for="<?= $column['name'] ?>" class="form-label"><?= ucwords(join(" ", explode("_", $column['reference_table'] ? $column['reference_table'] : $column['name']))) ?></label>
            <select class="form-select" name="<?= $column['name'] ?>">
              <?php foreach ($column['rows'] as $row) { ?>
                <?php $name = array_key_exists("first_name", $row) ? ($row['first_name'] . " " . $row['last_name']) : ($row['title'] ?? $row['name'] ?? $row['id']); ?>
                <option class='<?= $column['value'] == $row['id'] ? "text-success" : "" ?>' value='<?= $row['id'] ?>' data-row='<?= json_encode($row) ?>' <?= $column['value'] == $row['id'] ? 'selected' : '' ?>><?= $name; ?></option>
              <?php } ?>
            </select>
            <pre class="mt-2 bg-light p-3 rounded">Select a data to view its value</pre>
          </div>
        <?php } else { ?>
          <div class="mb-3">
            <label for="<?= $column['name'] ?>" class="form-label"><?= ucwords(join(" ", explode("_", $column['reference_table'] ? $column['reference_table'] : $column['name']))) ?></label>
            <input type="number" min="0" class="form-control" name="<?= $column['name'] ?>" value="<?= $column['value'] ?>">
          </div>
        <?php } ?>
      <?php } ?>

      <?php if ($column['type'] === 'datetime') { ?>
        <div class="mb-3">
          <label for="<?= $column['name'] ?>" class="form-label"><?= ucwords(join(" ", explode("_", $column['reference_table'] ? $column['reference_table'] : $column['name']))) ?></label>
          <input type="datetime-local" min="2020-12-31T00:00:00" name="<?= $column['name'] ?>" style="width:100%;" value="<?= str_replace(" ", "T", $column['value']) ?>">
        </div>
      <?php } ?>

      <?php if ($column['type'] === 'date') { ?>
        <div class=" mb-3">
          <label for="<?= $column['name'] ?>" class="form-label"><?= ucwords(join(" ", explode("_", $column['reference_table'] ? $column['reference_table'] : $column['name']))) ?></label>
          <input type="date" name="<?= $column['name'] ?>" style="width:100%;" value="<?= $column['value'] ?>">
        </div>
      <?php } ?>
    <?php } ?>

    <div class="d-flex justify-content-end">
      <button type="submit" class="btn btn-success m-2" onclick="return confirmButton();">Confirm</button>
      <button type="reset" class="btn btn-danger m-2">Clear</button>
    </div>
  </form>
<?php } ?>

<script>
  const data = <?= json_encode($mergedArray) ?>;
  const mappedDataName = data.map(({
    name
  }) => name);

  const allEl = [...document.querySelectorAll('select')];
  allEl.forEach((el) => {
    el.addEventListener('change', (evt) => {
      const selected = JSON.parse(el.options[el.selectedIndex].dataset.row);
      console.log(selected);
      const blockTag = el.parentElement.querySelector('pre');
      blockTag.innerHTML = prettyPrintJson.toHtml(selected);
    })
  })

  function confirmButton() {
    if (!confirm("proceed?")) {
      return false;
    }
    return true;
  }
</script>
<script>
  <?php
  if (isset($_SESSION['administrator']['error'])) {
    echo 'alert("' . $_SESSION['administrator']['error'] . '")';
    unset($_SESSION['administrator']['error']);
  }
  ?>
</script>