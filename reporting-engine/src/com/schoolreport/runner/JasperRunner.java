package com.schoolreport.runner;

import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.util.HashMap;
import java.util.Map;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import net.sf.jasperreports.engine.JasperCompileManager;
import net.sf.jasperreports.engine.JasperExportManager;
import net.sf.jasperreports.engine.JasperFillManager;
import net.sf.jasperreports.engine.JasperPrint;
import net.sf.jasperreports.engine.JasperReport;
import net.sf.jasperreports.engine.data.JsonDataSource;
import net.sf.jasperreports.engine.JREmptyDataSource;
import net.sf.jasperreports.engine.util.JRLoader;
import net.sf.jasperreports.engine.util.JRSaver;

public class JasperRunner {
    public static void main(String[] args) {
        String templatePath = null;
        String paramsArg = null;
        String dataPath = null;
        String outputPath = null;
        String format = "pdf";

        for (int i = 0; i < args.length; i++) {
            if ("--template".equals(args[i]) && i + 1 < args.length) {
                templatePath = args[++i];
            } else if ("--params".equals(args[i]) && i + 1 < args.length) {
                paramsArg = args[++i];
            } else if ("--data".equals(args[i]) && i + 1 < args.length) {
                dataPath = args[++i];
            } else if ("--output".equals(args[i]) && i + 1 < args.length) {
                outputPath = args[++i];
            } else if ("--format".equals(args[i]) && i + 1 < args.length) {
                format = args[++i];
            }
        }

        if (templatePath == null || outputPath == null) {
            System.err.println("Usage: JasperRunner --template <file.jrxml> --params <json_string_or_file> --data <data.json> --output <out.pdf> [--format pdf]");
            System.exit(1);
        }

        try {
            File templateFile = new File(templatePath);
            if (!templateFile.exists()) {
                System.err.println("Template file not found: " + templatePath);
                System.exit(2);
            }

            Map<String, Object> parameters = new HashMap<>();
            if (paramsArg != null && !paramsArg.trim().isEmpty()) {
                try {
                    String jsonContent = paramsArg;
                    File possibleFile = new File(paramsArg);
                    if (possibleFile.exists() && possibleFile.isFile()) {
                        jsonContent = new String(Files.readAllBytes(Paths.get(paramsArg)), "UTF-8");
                    }
                    ObjectMapper mapper = new ObjectMapper();
                    parameters = mapper.readValue(jsonContent, new TypeReference<Map<String, Object>>() {});
                } catch (Exception pe) {
                    System.err.println("Warning: Could not parse params JSON: " + pe.getMessage());
                }
            }

            // Check if pre-compiled .jasper exists and is up to date
            JasperReport jasperReport = null;
            String jasperPath = templatePath.replaceAll("\\.jrxml$", ".jasper");
            File jasperFile = new File(jasperPath);

            if (jasperFile.exists() && jasperFile.lastModified() >= templateFile.lastModified()) {
                try {
                    jasperReport = (JasperReport) JRLoader.loadObject(jasperFile);
                } catch (Exception ex) {
                    System.out.println("Notice: Recompiling fresh template: " + ex.getMessage());
                }
            }

            if (jasperReport == null) {
                jasperReport = JasperCompileManager.compileReport(templateFile.getAbsolutePath());
                try {
                    JRSaver.saveObject(jasperReport, jasperFile);
                } catch (Exception ex) {
                    System.err.println("Notice: Could not cache .jasper file: " + ex.getMessage());
                }
            }

            // Prepare Data Source
            JasperPrint jasperPrint;
            if (dataPath != null && new File(dataPath).exists()) {
                File dataFile = new File(dataPath);
                JsonDataSource ds = new JsonDataSource(dataFile);
                jasperPrint = JasperFillManager.fillReport(jasperReport, parameters, ds);
            } else {
                jasperPrint = JasperFillManager.fillReport(jasperReport, parameters, new JREmptyDataSource());
            }

            // Ensure destination folder exists
            File outputFile = new File(outputPath);
            if (outputFile.getParentFile() != null && !outputFile.getParentFile().exists()) {
                outputFile.getParentFile().mkdirs();
            }

            // Export to requested format
            if ("pdf".equalsIgnoreCase(format)) {
                JasperExportManager.exportReportToPdfFile(jasperPrint, outputPath);
            } else {
                JasperExportManager.exportReportToPdfFile(jasperPrint, outputPath);
            }

            System.out.println("SUCCESS: Report successfully exported to " + outputPath);
            System.exit(0);
        } catch (Throwable e) {
            System.err.println("JasperRunner Fatal Error: " + e.getMessage());
            e.printStackTrace(System.err);
            System.exit(3);
        }
    }
}
