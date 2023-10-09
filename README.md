# Database Activity filter for Moodle 3.11-4.x

A simple filter allowing the use of the `mod_data` plugin to allow usage of data sets in Moodle. These data sets are mapped to users using course groups.

The first intended usage was to provide data sets for questions from Moodle's question engine, as described in the Usage section below.

## Usage ##

### General usage ###

1. Create a database activity in a course, and set it to be unavailable to students. It is not necessary to have it set as unavailable, but it prevents from tampering or unwanted access to the raw data. The database activity name has to be name of the form `[A-Za-z0-9_]`, such as for instance "datasets".

2. Create the following fields:
    - datasetname: short text
    - itemname: short text
    - fieldname: text area OR short text

Any number of fields `fieldname` can be used, as long as the field name is of the form `[A-Za-z0-9_]`.

3. Use the database activity import feature to import from a CSV file formated as below, using quotes where necessary:
```
datasetname;itemname;"fieldname";..;..
```
For each dataset, there should be `n` items. Supposing there are `m` datasets, there would be `n * m` records in the CSV file / the Database activity. This step can also be achieved by manually creating records in the Database activity.

4. Create groups within the course for user-to-dataset mapping; these groups should be named `dataset_datasetname`. Then add students to a group, thus linking them to a dataset.

5. Whenever you want to display content from a user's dataset to the user, use the following syntax:
`{{database_activity_name:itemname:fieldname}}`

For instance, to use the item named "item1", and display the "data1" field from the user's current dataset, using datasets defined in the Database activity named "datasets", the syntax would be:
`{{datasets:item1:data1}}`
This would display the same item's "data1" value to different users, pulling the data from the data set matching the user's group name.


### Example: Usage as dataset for MCQ questions ###

1. Create a database activity in a course, and set it to be unavailable to students.

2. Create the following fields:
    - datasetname: short text
    - questionname: short text
    - questiontext: text area
    - answer1: short text
      ..
    - answerN: short text

The number `N` of question answers can be as big as necessary to account for the question with the highest number of possible answers.

3. Use the database activity import feature to import from a CSV file formated as below, using quotes where necessary:

```
datasetname;questionname;"questiontext";answer1;..;answerN
```
For each dataset, there should be `n` questions. Supposing there are `m` datasets, there would be `n * m` records in the CSV file.

4. Create groups within the course for user-to-dataset mapping; these groups should be named `questiondata_datasetname`. Then add students to a group, thus linking them to a dataset.

5. Create `n` quiz questions using the following syntax, which can be used in any HTML editor (e.g., question text, questions answers in a multiple choice questions, but _not_ in a short answer or calculated answer).
`{{questiondata:questionname:database_field}}`

For instance, to use the question named "question1", and display the "questiontext" field, the result would be:
`{{questiondata:question1:questiontext}}`

In the case of a MCQ, for the proposed answer 1, the syntax would be:
`{{questiondata:question1:answer1}}`


Usage as described in this example would display the same "question1" to all users, but pulling the actual question description ("questiontext") and proposed answers ("answer1" to "answerN") from the database activity entry corresponding to the data set assigned to the user.



