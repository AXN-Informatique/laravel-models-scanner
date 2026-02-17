<style>
    * {
        font-size: 11pt;
        font-family: Verdana;
    }

    .head {
        position: sticky;
        top: 0;
        z-index: 10;
        padding: 5px;
        background-color: #ccc;
    }

    .body {
        margin-bottom: 30px;
    }
    .body > div {
        padding: 20px;
    }
    .body > div:nth-child(even) {
        background-color: #eee;
    }

    .from-model {
        position: relative;
        text-align: left;
    }
    .from-model .indent {
        margin-top: 10px;
        margin-left: 20px;
        padding: 5px 0 5px 10px;
        border-left: 3px solid black;
    }
    .from-model .additionnal-info {
        position: absolute;
        top: 20px;
        right: 20px;
        text-align: right;
    }

    .from-db {
        position: relative;
        text-align: right;
    }
    .from-db .indent {
        margin-top: 10px;
        margin-right: 20px;
        padding: 5px 10px 5px 0;
        border-right: 3px solid black;
    }

    .muted {
        color: gray;
    }
    .green {
        color: green;
    }
    .red {
        color: red;
    }

    .BelongsTo {
        color: blue;
    }
    .HasMany {
        color: orange;
    }

    .miss-return-type {
        background-color: lightcoral;
    }

    .with-trashed {
        color: lightblue;
    }
    .without-trashed {
        color: lightcoral;
    }

    .declaration-source {
        background-color: lightcyan;
    }

    [data-relation-code] {
        cursor: pointer;
    }

    .copied {
        color: green !important;
        text-decoration: underline;
    }
</style>
