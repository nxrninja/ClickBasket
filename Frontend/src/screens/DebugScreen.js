import React, { useState } from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Button, Text, Card, Title, Paragraph } from 'react-native-paper';
import { API_CONFIG, API_CONFIGS, CURRENT_CONFIG } from '../config/apiConfig';
import { testNetworkConnection, getNetworkDiagnostics } from '../utils/networkTest';
import { theme } from '../theme/theme';

export default function DebugScreen() {
  const [testResult, setTestResult] = useState(null);
  const [testing, setTesting] = useState(false);

  const handleNetworkTest = async () => {
    setTesting(true);
    const result = await testNetworkConnection();
    setTestResult(result);
    setTesting(false);
  };

  const diagnostics = getNetworkDiagnostics();

  return (
    <ScrollView style={styles.container}>
      <Card style={styles.card}>
        <Card.Content>
          <Title>API Configuration Debug</Title>
          
          <View style={styles.section}>
            <Text style={styles.label}>Current Configuration:</Text>
            <Text style={styles.value}>{CURRENT_CONFIG}</Text>
          </View>
          
          <View style={styles.section}>
            <Text style={styles.label}>API Base URL:</Text>
            <Text style={styles.value}>{API_CONFIG.BASE_URL}</Text>
          </View>
          
          <View style={styles.section}>
            <Text style={styles.label}>Timeout:</Text>
            <Text style={styles.value}>{API_CONFIG.TIMEOUT}ms</Text>
          </View>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Title>Network Test</Title>
          
          <Button
            mode="contained"
            onPress={handleNetworkTest}
            loading={testing}
            disabled={testing}
            style={styles.button}
          >
            Test Network Connection
          </Button>
          
          {testResult && (
            <View style={[styles.result, testResult.success ? styles.success : styles.error]}>
              <Text style={styles.resultText}>
                {testResult.success ? '✅ ' : '❌ '}
                {testResult.message}
              </Text>
            </View>
          )}
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Title>Available Configurations</Title>
          
          {Object.entries(API_CONFIGS).map(([key, config]) => (
            <View key={key} style={styles.configItem}>
              <Text style={styles.configName}>{key}:</Text>
              <Text style={styles.configUrl}>{config.BASE_URL}</Text>
              <Text style={styles.configDesc}>{config.DESCRIPTION}</Text>
            </View>
          ))}
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Title>Troubleshooting Tips</Title>
          
          {diagnostics.suggestions.map((suggestion, index) => (
            <Paragraph key={index} style={styles.suggestion}>
              {suggestion}
            </Paragraph>
          ))}
        </Card.Content>
      </Card>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme.colors.background,
    padding: theme.spacing.md,
  },
  card: {
    marginBottom: theme.spacing.md,
    elevation: 4,
  },
  section: {
    marginBottom: theme.spacing.md,
  },
  label: {
    fontWeight: 'bold',
    color: theme.colors.primary,
    marginBottom: theme.spacing.xs,
  },
  value: {
    fontFamily: 'monospace',
    backgroundColor: theme.colors.surface,
    padding: theme.spacing.sm,
    borderRadius: theme.borderRadius.sm,
  },
  button: {
    marginVertical: theme.spacing.md,
  },
  result: {
    padding: theme.spacing.md,
    borderRadius: theme.borderRadius.sm,
    marginTop: theme.spacing.sm,
  },
  success: {
    backgroundColor: '#e8f5e8',
  },
  error: {
    backgroundColor: '#ffeaea',
  },
  resultText: {
    fontWeight: 'bold',
  },
  configItem: {
    marginBottom: theme.spacing.md,
    padding: theme.spacing.sm,
    backgroundColor: theme.colors.surface,
    borderRadius: theme.borderRadius.sm,
  },
  configName: {
    fontWeight: 'bold',
    color: theme.colors.primary,
  },
  configUrl: {
    fontFamily: 'monospace',
    fontSize: 12,
    color: theme.colors.text,
  },
  configDesc: {
    fontSize: 12,
    color: theme.colors.placeholder,
    fontStyle: 'italic',
  },
  suggestion: {
    marginBottom: theme.spacing.xs,
  },
});
