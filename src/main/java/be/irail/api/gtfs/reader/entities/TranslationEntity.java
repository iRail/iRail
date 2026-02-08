package be.irail.api.gtfs.reader.entities;

import org.onebusaway.csv_entities.schema.annotations.CsvField;
import org.onebusaway.gtfs.model.IdentityBean;
import org.onebusaway.csv_entities.schema.annotations.CsvFields;

@CsvFields(filename = "translations.txt")
public class TranslationEntity extends IdentityBean<Integer> {

    private static final long serialVersionUID = 1L;

    @CsvField(ignore = true)
    private int id;

    @CsvField(name = "table_name")
    private String tableName;

    @CsvField(name = "field_name")
    private String fieldName;

    @CsvField(name = "language")
    private String language;

    @CsvField(name = "translation")
    private String translation;

    @CsvField(name = "record_id", optional = true)
    private String recordId;

    @CsvField(name = "record_sub_id", optional = true)
    private String recordSubId;

    @CsvField(name = "field_value", optional = true)
    private String fieldValue;

    @Override
    public Integer getId() {
        return id;
    }

    @Override
    public void setId(Integer id) {
        this.id = id;
    }

    public String getTableName() {
        return tableName;
    }

    public void setTableName(String tableName) {
        this.tableName = tableName;
    }

    public String getFieldName() {
        return fieldName;
    }

    public void setFieldName(String fieldName) {
        this.fieldName = fieldName;
    }

    public String getLanguage() {
        return language;
    }

    public void setLanguage(String language) {
        this.language = language;
    }

    public String getTranslation() {
        return translation;
    }

    public void setTranslation(String translation) {
        this.translation = translation;
    }

    public String getRecordId() {
        return recordId;
    }

    public void setRecordId(String recordId) {
        this.recordId = recordId;
    }

    public String getRecordSubId() {
        return recordSubId;
    }

    public void setRecordSubId(String recordSubId) {
        this.recordSubId = recordSubId;
    }

    public String getFieldValue() {
        return fieldValue;
    }

    public void setFieldValue(String fieldValue) {
        this.fieldValue = fieldValue;
    }
}
