# filter_questiondata for Moodle 3.11-4.x

A simple filter allowing the use of the `mod_data` plugin to allow usage of data sets in the question engine.

## Usage ##

1. Create a database activity in a course, and set it to be unavailable to students.

2. Create the following fields:
    - datasetname: short text
    - questionname: short text
    - questiontext: text area
    - answer1: short text
      ..
    - answerN: short text

The number `N` of question answers can be as big as necessary to account for the question with the most answering options.

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


## Result ##

Usage as described above would display the same "question1" to all users, but pulling the actual question description ("questiontext") and proposed answers ("answer1" to "answerN") from the database activity entry corresponding to the data set assigned to the user.



