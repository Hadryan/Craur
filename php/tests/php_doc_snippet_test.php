<?php
/* Craur#createFromJson */

$node = Craur::createFromJson('{"book": {"authors": ["Hans", "Paul"]}}');
$authors = $node->get('book.authors[]');
assert(count($authors) == 2);


/* Craur#createFromXml */

$node = Craur::createFromXml('<book><author>Hans</author><author>Paul</author></book>');
$authors = $node->get('book.author[]');
assert(count($authors) == 2);


/* Craur#createFromCsvFile */

// If the file loooks like this:
// Book Name;Book Year;Author Name
// My Book;2012;Hans
// My Book;2012;Paul
// My second Book;2010;Erwin
$shelf = Craur::createFromCsvFile('fixtures/books.csv', array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
));
assert(count($shelf->get('book[]')) === 2);
foreach ($shelf->get('book[]') as $book)
{
    assert(in_array($book->get('name'), array('My Book', 'My second Book')));
    foreach ($book->get('author[]') as $author)
    {
        assert(in_array($author->get('name'), array('Hans', 'Paul', 'Erwin')));
    }
}


/* Craur#getValues */

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$values = $node->getValues(
    array(
        'name' => 'book.name',
        'book_price' => 'price',
        'first_author' => 'book.authors'
    ),
    array(
        'book_price' => 20
    )
);

assert($values['name'] == 'MyBook');
assert($values['book_price'] == '20');
assert($values['first_author'] == 'Hans');


/* Craur#getValuesWithFilters */

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$values = $node->getValuesWithFilters(
    array(
        'name' => 'book.name',
        'book_price' => 'price',
        'first_author' => 'book.authors'
    ),
    array(
        'name' => 'strtolower',
        'first_author' => 'strtoupper',
    ),
    array(
        'book_price' => 20
    )
);

assert($values['name'] == 'mybook');
assert($values['book_price'] == '20');
assert($values['first_author'] == 'HANS');


/* Craur#get */

$node = Craur::createFromJson('{"book": {"name": "MyBook", "authors": ["Hans", "Paul"]}}');

$book = $node->get('book');
assert($book->get('name') == 'MyBook');
assert($book->get('price', 20) == 20);

$authors = $node->get('book.authors[]');
assert(count($authors) == 2);


/* Craur#getWithFilter */

function isACheapBook(Craur $value)
{
    if ($value->get('price') > 20)
    {
        throw new Exception('Is no cheap book!');
    }
    return $value;
}

$node = Craur::createFromJson('{"books": [{"name":"A", "price": 30}, {"name": "B", "price": 10}, {"name": "C", "price": 15}]}');
$cheap_books = $node->getWithFilter('books[]', 'isACheapBook');
assert(count($cheap_books) == 2);
assert($cheap_books[0]->get('name') == 'B');
assert($cheap_books[1]->get('name') == 'C');


/* Craur#saveToCsvFile */

$data = array(
    'book' => array(
        array(
            'name' => 'My Book',
            'year' => '2012',
            'author' => array(
                array('name' => 'Hans'),
                array('name' => 'Paul')
            )
        ),
        array(
            'name' => 'My second Book',
            'year' => '2010',
            'author' => array(
                array('name' => 'Erwin')
            )
        )
    )
);

$shelf = new Craur($data);
$shelf->saveToCsvFile('fixtures/temp_csv_file.csv', array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
));

// csv file will look like this now:
// book[].name;book[].year;book[].author[].name
// "My Book";2012;Hans
// "My Book";2012;Paul
// "My second Book";2010;Erwin

assert(json_encode(array($data)) == Craur::createFromCsvFile('fixtures/temp_csv_file.csv', array(
    'book[].name',
    'book[].year',
    'book[].author[].name',
))->toJsonString());

unlink('fixtures/temp_csv_file.csv');


/* CraurCsvWriter#extractDirectDescendants */

$craur = new Craur(
    array(
        'name' => 'My Book',
        'year' => '2012',
        'categories' => array( // Will be ignored
            'comedy',          
            'fantasy'           
        ),
        'authors' => array( // Will be ignored
            array('name' => 'Paul'),
            array('name' => 'Erwin')
        ),
        'pages' => '212'
    )
);

$expected_data = array(
    0 => 'My Book',
    1 => '2012',
    4 => '212'
);

$result_data = CraurCsvWriter::extractDirectDescendants($craur, array(
    'name',
    'year',
    'categories[]',
    'authors[].name',
    'pages',
),'');

assert(json_encode($expected_data) == json_encode($result_data)); 

/* CraurCsvWriter#extractAllDescendants */

$craur = new Craur(
    array(
        'name' => 'My Book',
        'year' => '2012',
        'authors' => array(
            array('name' => 'Paul', 'age' => '30'),
            array('name' => 'Erwin', 'age' => '20'),
        ),
        'categories' => array(
            'comedy',
            'fantasy'
        ),
        'pages' => '212'
    )
);

$expected_data = array(
    array(
        'My Book',
        '2012',
        'Paul',
        '30',
        'comedy',
        '212'
    ),
    array(
        'My Book',
        '2012',
        'Erwin',
        '20',
        'fantasy',
        '212'
    )
);

$result_data = CraurCsvWriter::extractAllDescendants($craur, array(
    'name',
    'year',
    'authors[].name',
    'authors[].age',
    'categories[]',
    'pages',
),'');

assert(json_encode($expected_data) == json_encode($result_data));


