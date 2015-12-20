<h1 class="legend">Zend Active Record</h1>

<p>The active record pattern is one where an object in the application maps to a single row in the database.
This object exposes CRUD (create, read, update, and delete) methods for the related row.</p>

<h2>Installation</h2>
`composer require digitalus/zend-active-record`

<h2>This Implementation</h2>
<p>All of the AR (active record) models extend the <code>ZendActiveRecord\Model</code> base class which handles
the underlying functionality. The implementation is simplistic and light weight by design, to avoid any
interference between the AR implementation and individual model logic. The only dependency is a db adapter
to handle db interaction.</p>

<h2>Creating a Model</h2>
<p>I'll model the fictitious fish table for an example. The model will have public properties for each
of the fields in the fish table.</p>
<pre>
namespace Application\Model; // depending on the module

use ZendActiveRecord\Model;

class Fish  extends Model {
    protected $tableName = 'fish';

    public $id;
    public $name;
    public $taste;
}
</pre>

<h2>Inserting a record</h2>
<p>You insert a record into the database by creating an instance of the Fish model, then saving it.</p>

<pre>
$tuna = new Fish;
$tuna->name = 'bluefin tuna';
$tuna->taste = 'delicious';
$tuna->save();
</pre>

<p class="alert alert-info"><strong>Note</strong> When you call save the AR checks to see if the id is set, and since its not it inserts a record.</p>

<h2>Finding Records</h2>
<p>If you already know the id of the row you are looking for <code>find()</code> is the simplest way to fetch the row
instance.</p>

<pre>
$fishModel = new Fish($dbAdapter);
$tunaId = 999;
$tuna = $fishModel->find($tunaId);
</pre>

<h2>Querying</h2>
<p>There are two methods to fetch data:</p>
<ul>
    <li>fetchRow($where)</li>
    <li>fetchAll($where)</li>
</ul>

<p>Both of these methods accept where conditions which can be null (return all), a Select object, or an array.
The Select object gives you the most control.</p>

<pre>
$fishModel = new Fish($dbAdapter);

// fetch all fish
$allFish = $fishModel->fetchAll();

// find delicious fish
// with an array of filters
$filters = array('taste' => 'delicious');
$deliciousFish = $fishModel->fetchAll($filters);

// find them with the Zend Select object
$select = $fishModel->select();
$select->where('taste = "delicious"');
$select->order('name')
$deliciousFish = $fishModel->fetchAll($select);
</pre>

<p>Full documentation for the Zend Select object is available at: <a href="http://framework.zend.com/manual/2.2/en/modules/zend.db.sql.html#zend-db-sql-select">http://framework.zend.com/manual/2.2/en/modules/zend.db.sql.html#zend-db-sql-select</a></p>

<h2>Updating a Record</h2>
<p>The functions above return instances (or a single instance) of the Fish object. This object is editable.</p>
<pre>
$tunaId = 999;
$tuna = $fishModel->find($tunaId);
$tuna->taste = 'best thing in the world';
$tuna->save();
</pre>


<h2>Deleting a Record</h2>
<p>You can delete any active record object, which will delete the underlying row in the database.</p>

<pre>
$tunaId = 999;
$tuna = $fishModel->find($tunaId);
$tuna->delete();
</pre>

<p>If you have the id instead of an active record object you can pass the delete method the id of the row to remove.</p>
<pre>
$tunaId = 999;
$fishModel->delete($tunaId);
</pre>
